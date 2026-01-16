# Auto-Context — MVP на Laravel (Архитектурный документ)

Ты — одновременно Senior Solution Architect и Senior Product Manager, хорошо знающий Laravel, PHP, PostgreSQL, Redis, Docker и high-load backend.

Цель: спроектировать и разложить на задачи MVP продукта **Auto-Context** на стеке:

- Backend: Laravel (PHP)
- БД: PostgreSQL
- Очереди/кэш: Redis
- Инфраструктура: Docker (docker-compose, опционально Kubernetes в будущем)
- HTTP API (REST), JSON

---

## 1. Архитектура MVP

### 1.1. Основные компоненты

#### 1) HTTP API для логов

- Endpoint: `POST /api/logs`
- Формат: JSON (один лог или батч `{ "events": [...] }`)
- Авторизация: `X-Api-Key: <key>`
- Компоненты:
  - `App\Http\Controllers\Api\LogIngestController`
  - Middleware `ApiKeyAuth` (по заголовку `X-Api-Key`)
  - DTO/сервис нормализации логов `LogNormalizer`
  - Очередь: публикация `ProcessLogBatchJob`

#### 2) HTTP API для деплоев

- Endpoint: `POST /api/deployments`
- Авторизация: тот же `X-Api-Key`
- Компоненты:
  - `App\Http\Controllers\Api\DeploymentController`
  - Модель `Deployment`
  - Валидация: `version`, `started_at`, `finished_at?`, `region?`, `metadata?`

#### 3) Слой авторизации / API-ключей

- Таблица `api_keys`
- Middleware:
  - `App\Http\Middleware\ApiKeyAuth`:
    - Ищет `ApiKey` по `key`, `is_active = true`
    - Вешает в `request` объект `project`
- Для веб-UI — отдельная простая аутентификация (`users` + `auth:web`).

#### 4) Очереди и Jobs

Основной пайплайн для одного батча логов:

- `ProcessLogBatchJob`
  - вход: `project_id`, массив сырых событий
  - для каждого события выполняются шаги:
    - `LogContextEnricher::enrich($event)`
    - `LogFilter::shouldDrop($event)`
    - если не дропнут:
      - `LogForwarder::forward($event)`
      - `StatsRecorder::record(...)`

Для MVP достаточно **одного Job** + нескольких внутренних сервисов:

- `ProcessLogBatchJob`
  - `LogContextEnricher` — привязка к деплою, сервису, региону
  - `LogFilter` — шум, уровни, health-check-и, повторяющиеся ошибки
  - `LogForwarder` — отправка в downstream
  - `StatsRecorder` — учёт incoming/outgoing/filtered/deployment_errors

#### 5) Подсистема статистики

- Сервис:
  - `StatsRecorder`:
    - инкрементирует счётчики в Redis:  
      `stats:{project_id}:{YYYYMMDDHH}:incoming`  
      `stats:{project_id}:{YYYYMMDDHH}:outgoing`  
      `stats:{project_id}:{YYYYMMDDHH}:filtered`  
      `stats:{project_id}:{YYYYMMDDHH}:deployment_errors`
- Cron/job:
  - `HourlyStatsFlushJob`:
    - раз в час считывает Redis-ключи и делает upsert в таблицу `project_hourly_stats`.

#### 6) Веб-дашборд

- Технология: Laravel Blade + немного Alpine/Livewire/Chart.js (MVP)
- Компоненты:
  - `App\Http\Controllers\Web\ProjectDashboardController`
  - Вьюхи:
    - `/projects` — список проектов
    - `/projects/{id}` — дашборд
- Отображение:
  - Входящие/исходящие логи за период
  - % экономии
  - Ошибки, связанные с последним деплоем
  - Список последних деплоев + статистика

#### 7) Админка / управление проектами и API-ключами

- CRUD:
  - `ProjectController` (web)
  - `ApiKeyController` (web)
- Функционал:
  - создать проект
  - сгенерировать API-ключ
  - отключить ключ
  - настроить downstream endpoint (URL, тип, headers)

#### 8) Downstream форвардинг

- Модель `DownstreamEndpoint`:
  - тип: `http`, `file`, `s3` (в MVP — `http` + `file`)
- Сервис:
  - `LogForwarder`:
    - `http`: Guzzle POST в `endpoint_url`
    - `file`: запись в локальный файл/rotating log
- При ошибках:
  - помечаем event как `forward_failed` и увеличиваем отдельный счётчик.

---

### 1.2. Data-flow (поток данных)

#### Путь логов

1. Клиентское приложение:
   - Отправляет `POST /api/logs` с `X-Api-Key` и JSON (одиночный event или батч `events`).
2. `LogIngestController`:
   - Авторизация по API-ключу → `project_id`.
   - Валидация схемы (timestamp, level, message, context?).
   - Публикация `ProcessLogBatchJob($project_id, $events)` в очередь.
3. `ProcessLogBatchJob` (worker, Redis-очередь):
   - Для каждого события:
     1. `StatsRecorder`: incoming++ (Redis).
     2. `LogContextEnricher`:
        - Находит релевантный `Deployment`:
          - последний деплой проекта, где `started_at <= event.timestamp`
          - при наличии `finished_at` — проверка `event.timestamp <= finished_at`
        - Записывает:
          - `deployment_id`
          - `deployment_version`
          - `deployment_related = true/false` (например, в окне N минут после деплоя).
          - `service`, `region` (из event или настроек проекта).
     3. `LogFilter`:
        - `level in [DEBUG, TRACE]` → дроп.
        - health-check пути `/health`, `/metrics`, `/ping` → дроп.
        - повторяющиеся ошибки:
          - считаем `error_hash` (message + stack + type).
          - Redis `INCR` по `err:{project_id}:{hash}:{minute}`.
          - если count > threshold → дроп.
     4. Если **не дропнут**:
        - `LogForwarder::forward($event)`:
          - отправка в downstream (`http`/`file`).
        - `StatsRecorder`: outgoing++.
        - Если `deployment_related = true` и `level in [ERROR, FATAL]` → deployment_errors++.
        - (опционально) запись в `log_events`.
     5. Если **дропнут**:
        - `StatsRecorder`: filtered++.
4. `HourlyStatsFlushJob`:
   - Раз в час сбрасывает Redis-счётчики в `project_hourly_stats`.
5. Web-дэшборд:
   - Читает `project_hourly_stats`, `deployments`, `aggregated_errors` для графиков.

#### Путь деплоев

1. CI/CD или деплой-сервис:
   - `POST /api/deployments` с `X-Api-Key`.
   - Тело: `{ "version", "started_at", "finished_at"?, "region"?, "environment"?, "metadata"? }`.
2. `DeploymentController`:
   - Авторизация по API-ключу → `project_id`.
   - Создание записи `deployments`.
3. При обработке логов:
   - `LogContextEnricher` использует `deployments` проекта для привязки log-event к конкретному деплою.

---

## 2. Модель данных (PostgreSQL / Laravel Models)

### 2.1. `projects`

- `id` (bigint, PK)
- `name` (string)
- `slug` (string, unique)
- `status` (enum: `active`, `inactive`)
- `default_region` (string, nullable)
- `created_at`, `updated_at`

**Model:** `App\Models\Project`

---

### 2.2. `api_keys`

- `id` (bigint, PK)
- `project_id` (FK → projects)
- `key` (string, unique, индекс)
- `is_active` (boolean)
- `created_at`, `updated_at`
- `last_used_at` (timestamp, nullable)
- `description` (string, nullable)

**Model:** `App\Models\ApiKey`

---

### 2.3. `deployments`

- `id` (bigint, PK)
- `project_id` (FK → projects, индекс)
- `version` (string)
- `environment` (string, nullable — `prod`, `staging`)
- `region` (string, nullable)
- `started_at` (timestamp)
- `finished_at` (timestamp, nullable)
- `metadata` (jsonb, nullable)
- Индексы:
  - `(project_id, started_at DESC)`

**Model:** `App\Models\Deployment`

---

### 2.4. `downstream_endpoints`

- `id` (bigint, PK)
- `project_id` (FK → projects)
- `type` (enum: `http`, `file`, `s3` — в MVP `http` + `file`)
- `endpoint_url` (string, nullable для `file`)
- `config` (jsonb) — headers, auth, путь к файлу и т.д.
- `is_active` (boolean)
- `created_at`, `updated_at`

**Model:** `App\Models\DownstreamEndpoint`

---

### 2.5. `log_events` (опционально, обогащённые события)

- `id` (bigint, PK)
- `project_id` (FK)
- `deployment_id` (FK, nullable)
- `timestamp` (timestamp)
- `level` (string)
- `message` (text)
- `service` (string, nullable)
- `region` (string, nullable)
- `payload` (jsonb) — оригинальный + обогащенный контекст
- `deployment_related` (boolean)
- Индексы:
  - `(project_id, timestamp DESC)`
  - `(project_id, deployment_id)`

**Model:** `App\Models\LogEvent`

---

### 2.6. `aggregated_errors`

- `id` (bigint, PK)
- `project_id` (FK)
- `error_hash` (string, индекс)
- `last_message` (text)
- `level` (string)
- `last_seen_at` (timestamp)
- `count_total` (bigint)
- `count_since_last_deploy` (bigint)
- `last_deployment_id` (bigint, nullable)
- `sample_event` (jsonb, nullable)

**Model:** `App\Models\AggregatedError`

---

### 2.7. `project_hourly_stats`

- `id` (bigint, PK)
- `project_id` (FK)
- `hour_ts` (timestamp без минут/секунд — начало часа)
- `incoming_count` (bigint)
- `outgoing_count` (bigint)
- `filtered_count` (bigint)
- `deployment_error_count` (bigint)
- Уникальный индекс: `(project_id, hour_ts)`

**Model:** `App\Models\ProjectHourlyStat`

---

## 3. Эпики

1. **Ingestion & Auth (Подключение источников логов)**
   - Goal: DevOps может отправлять JSON-логи в единый endpoint с API-ключом.
   - Компоненты:
     - `LogIngestController`, `ApiKeyAuth` middleware
     - `Project`, `ApiKey` модели
     - `ProcessLogBatchJob`, очередь

2. **Deployment Context Engine**
   - Goal: Привязка логов к деплою и маркировка событий, связанных с последним деплоем.
   - Компоненты:
     - `DeploymentController`, `Deployment` модель
     - `LogContextEnricher` сервис
     - Частично `aggregated_errors`

3. **Filtering & Aggregation**
   - Goal: Удалять шум и агрегировать повторяющиеся ошибки.
   - Компоненты:
     - `LogFilter`
     - Redis для детекции повторов
     - `aggregated_errors` + `ErrorAggregator`

4. **Forwarding**
   - Goal: Отправка очищенного потока логов в downstream (HTTP/file).
   - Компоненты:
     - `DownstreamEndpoint` модель
     - `LogForwarder`
     - UI настройки

5. **Dashboard & Analytics**
   - Goal: Показать DevOps/SRE экономию, объём логов и связь с деплоем.
   - Компоненты:
     - `ProjectHourlyStat`
     - `StatsRecorder`, `HourlyStatsFlushJob`
     - `ProjectDashboardController`, Blade-шаблоны

6. **Multi-tenant basics (Projects & API Keys Management)**
   - Goal: Создание/управление проектами, API-ключами и downstream-настройками.
   - Компоненты:
     - `ProjectController`, `ApiKeyController`, `DownstreamEndpointController`
     - Web auth (`User` модель)

7. **Infrastructure & DevOps**
   - Goal: Запуск всего стека через Docker.
   - Компоненты:
     - `docker-compose.yml`, Dockerfile
     - env-конфиг для PostgreSQL/Redis

---

## 4. User Stories + Acceptance Criteria

### Эпик: Ingestion & Auth

**Story 1**  
*Как DevOps, я хочу отправлять JSON-логи в единый endpoint с API-ключом, чтобы не менять сильно существующую систему логирования.*

- Acceptance:
  - Endpoint `POST /api/logs`.
  - Авторизация по `X-Api-Key`.
  - При неверном/отключённом ключе — 401/403.
  - Валидные события кладутся в очередь, ответ — 202 Accepted.

**Story 2**  
*Как DevOps, я хочу отправлять батч логов, чтобы сократить overhead на сеть и HTTP.*

- Acceptance:
  - Endpoint принимает один объект и `{ "events": [...] }`.
  - Все валидные события батча попадают в очередь.
  - В ответе — количество принятых событий.

---

### Эпик: Deployment Context Engine

**Story 3**  
*Как SRE, я хочу регистрировать деплой через API, чтобы система знала, какие ошибки связаны с конкретным релизом.*

- Acceptance:
  - Endpoint `POST /api/deployments`.
  - Поля: `version`, `started_at`, `finished_at?`, `environment?`, `region?`, `metadata?`.
  - Запись создаётся в `deployments` с привязкой к project.
  - Доступ по API-ключу.

**Story 4**  
*Как SRE, я хочу видеть, какие логи были связаны с последним деплоем, чтобы быстрее находить причины инцидентов.*

- Acceptance:
  - При обработке логов события получают:
    - `deployment_id`, `deployment_version`, `deployment_related`.
  - Лог считается `deployment_related`, если:
    - его timestamp попадает в окно N минут после `started_at` последнего деплоя (или до `finished_at`, если задан).
  - На дашборде есть показатель “Ошибки, связанные с последним деплоем” за период.

---

### Эпик: Filtering & Aggregation

**Story 5**  
*Как DevOps, я хочу фильтровать DEBUG/TRACE и health-check запросы, чтобы не тратить деньги на шум.*

- Acceptance:
  - Конфиг по умолчанию: уровни `DEBUG`, `TRACE` — дроп.
  - Запросы с путями `/health`, `/ping`, `/metrics` — дроп.
  - Отфильтрованные события не идут в downstream, но учитываются как входящие.

**Story 6**  
*Как SRE, я хочу, чтобы повторяющиеся ошибки агрегировались, а не слались миллионами одинаковых логов.*

- Acceptance:
  - Для ошибок (`level >= ERROR`) считается `error_hash`.
  - За минуту по этому hash > N событий → последующие дропаются.
  - В `aggregated_errors` обновляется счётчик и `last_seen_at`.

---

### Эпик: Forwarding

**Story 7**  
*Как DevOps, я хочу перенаправлять очищенные логи в существующую систему (например, HTTP endpoint), чтобы интеграция была минимальной.*

- Acceptance:
  - Настройка downstream endpoint на уровне проекта (тип: `http`).
  - Все неотфильтрованные события отправляются POST-запросом на этот endpoint.
  - При ошибке downstream система логирует failure и увеличивает соответствующий счётчик.

---

### Эпик: Dashboard & Analytics

**Story 8**  
*Как Platform-инженер, я хочу видеть, насколько уменьшился объём отправляемых логов, чтобы оценить экономию.*

- Acceptance:
  - На странице проекта:
    - Входящие события за период.
    - Исходящие события.
    - % экономии = `(1 - outgoing/incoming) * 100`.
  - Графики по часам/дням на основе `project_hourly_stats`.

**Story 9**  
*Как SRE, я хочу видеть статистику ошибок, связанных с деплоем, чтобы быстро понять, “сломал ли релиз прод”.*

- Acceptance:
  - На дашборде:
    - Количество `deployment_related` ошибок за период.
    - Список последних деплоев и количество ошибок вокруг каждого.

---

### Эпик: Multi-tenant basics

**Story 10**  
*Как владелец платформы, я хочу создавать проекты и API-ключи через веб-панель, чтобы быстро подключать новые сервисы.*

- Acceptance:
  - Страница списка проектов.
  - Форма создания/редактирования проекта.
  - Внутри проекта — список API-ключей, CRUD для ключей (генерация, отключение).

---

### Эпик: Infrastructure & DevOps

**Story 11**  
*Как DevOps, я хочу запускать Auto-Context одной командой через Docker, чтобы быстро развернуть MVP для теста.*

- Acceptance:
  - `docker-compose.yml` c сервисами:
    - `app` (php-fpm)
    - `nginx`
    - `postgres`
    - `redis`
    - `queue-worker`
  - README с инструкцией запуска.
  - Миграции и очереди работают через `docker-compose up` + `php artisan migrate`.

---

## 5. Backlog задач (для Jira/Linear)

### Эпик: Infrastructure & DevOps

**Task:** Инициализация Laravel-проекта + базовый Docker  
- Acceptance:
  - Laravel-проект в репозитории.
  - `Dockerfile` для php-fpm.
  - `docker-compose.yml` с nginx, app, postgres, redis.
- Dependencies: нет.

**Task:** Настройка queue-worker в Docker  
- Acceptance:
  - Сервис `queue-worker` (supervisor или `php artisan queue:work`).
  - Очередь `redis` работает, тестовый job обрабатывается.
- Dependencies: базовый Docker.

---

### Эпик: Multi-tenant basics

**Task:** Миграции и модели `projects`, `api_keys`  
- Acceptance:
  - Миграции созданы.
  - Модели `Project`, `ApiKey`.
  - Связь `Project hasMany ApiKey`.

**Task:** Middleware `ApiKeyAuth`  
- Acceptance:
  - Middleware создан.
  - Подключен к API-роутам.
  - При валидном `X-Api-Key` в запросе доступен `project`.
  - При неверном/отключённом ключе — 401.

**Task:** Web-auth для админки  
- Acceptance:
  - Модель `User`, миграция.
  - Login-форма, `auth:web`.
  - Middleware `auth` на web-роуты.

**Task:** CRUD для проектов  
- Acceptance:
  - Список проектов.
  - Создание/редактирование/деактивация.

**Task:** CRUD для API-ключей проекта  
- Acceptance:
  - Список ключей.
  - Генерация нового ключа.
  - Деактивация/удаление.

---

### Эпик: Ingestion & Auth

**Task:** Endpoint `POST /api/logs` + валидация  
- Acceptance:
  - Принимает один лог и батч `events`.
  - Проверка минимальных полей (`timestamp`, `level`, `message`).
  - Возвращает 202 + количество принятых событий.
- Dependencies: `ApiKeyAuth`.

**Task:** Job `ProcessLogBatchJob` (базовая версия)  
- Acceptance:
  - Job с сигнатурой `__construct(int $projectId, array $events)`.
  - Публикуется из контроллера.
  - Worker обрабатывает (пока простая логика — запись в лог).

---

### Эпик: Deployment Context Engine

**Task:** Миграция/модель `deployments`  
- Acceptance:
  - Таблица `deployments` по схеме.
  - Модель `Deployment` с `belongsTo(Project)`.

**Task:** Endpoint `POST /api/deployments`  
- Acceptance:
  - Валидация: `version`, `started_at`, optionally `finished_at`, `environment`, `region`, `metadata`.
  - Создаёт запись в БД.
  - Возвращает JSON объекта деплоя.

**Task:** Сервис `LogContextEnricher`  
- Acceptance:
  - Метод `enrich(Project $project, array $event): array`.
  - Находит релевантный deployment.
  - Добавляет: `deployment_id`, `deployment_version`, `deployment_related`.
  - Unit-тесты логики окна N минут.

---

### Эпик: Filtering & Aggregation

**Task:** Сервис `LogFilter`  
- Acceptance:
  - Метод `shouldDrop(array $event): bool`.
  - Дропает:
    - `level in ["DEBUG","TRACE"]`.
    - `path in ["/health","/metrics","/ping"]`.
  - Конфиг в `config/log_filter.php`.

**Task:** Redis-счетчики повторяющихся ошибок  
- Acceptance:
  - Вычисление `error_hash`.
  - Redis-ключ `err:{project_id}:{hash}:{YYYYMMDDHHMM}` INCR.
  - Если count > threshold — `shouldDrop` возвращает true.
  - threshold в конфиге.

**Task:** Миграция/модель `aggregated_errors`  
- Acceptance:
  - Таблица по схеме.
  - Модель с методом upsert по `(project_id, error_hash)`.

**Task:** Сервис `ErrorAggregator`  
- Acceptance:
  - Метод `record($projectId, $event, $deploymentId = null)`.
  - Обновляет/создаёт запись в `aggregated_errors`.
  - Обновляет `last_message`, `last_seen_at`, счётчики.

---

### Эпик: Forwarding

**Task:** Миграция/модель `downstream_endpoints`  
- Acceptance:
  - Таблица по схеме.
  - Связь `Project hasOne DownstreamEndpoint`.

**Task:** Сервис `LogForwarder` (HTTP + file)  
- Acceptance:
  - `http`:
    - POST JSON на заданный URL.
    - Учитывает headers из `config`.
  - `file`:
    - Запись строки JSON в файл (`storage/logs/downstream_{project}.log`).
  - Обработка исключений.

**Task:** Интеграция `LogForwarder` в `ProcessLogBatchJob`  
- Acceptance:
  - После фильтрации неотфильтрованные события отправляются в downstream.
  - Ошибки отправки не ломают job, но логируются и учитываются.

**Task:** UI для настройки downstream endpoint  
- Acceptance:
  - Форма в админке проекта.
  - Выбор типа endpoint, задание URL/config.
  - Валидация и сохранение.

---

### Эпик: Dashboard & Analytics

**Task:** Миграция/модель `project_hourly_stats`  
- Acceptance:
  - Таблица по схеме.
  - Уникальный индекс `(project_id, hour_ts)`.

**Task:** Сервис `StatsRecorder`  
- Acceptance:
  - Методы:
    - `recordIncoming($projectId)`
    - `recordOutgoing($projectId)`
    - `recordFiltered($projectId)`
    - `recordDeploymentError($projectId)`
  - Все операции — инкременты Redis-ключей по часам.

**Task:** Job `HourlyStatsFlushJob`  
- Acceptance:
  - Читает Redis-ключи за последние M часов.
  - Upsert в `project_hourly_stats`.
  - Удаляет обработанные ключи.

**Task:** Web-дэшборд проекта  
- Acceptance:
  - Страница `/projects/{id}/dashboard`.
  - Период по умолчанию — последние 24 часа/7 дней.
  - Показатели: incoming, outgoing, filtered, % экономии, deployment_error_count.
  - Простые графики (Chart.js или аналог).

---

### Эпик: UX & Demo подготовка

**Task:** Список последних деплоев на дашборде  
- Acceptance:
  - Таблица последних N деплоев (version, started_at, количество ошибок вокруг деплоя).

**Task:** Seed-скрипт для демо-проекта  
- Acceptance:
  - Команда `php artisan demo:seed` создаёт:
    - тестовый проект;
    - API-ключ;
    - тестовый downstream (file);
    - пару деплоев.
  - Документация в README.

---

## 6. Roadmap MVP (2–3 недели)

### Итерация 1 (Неделя 1): Core ingestion + deployments + basic filtering

Фокус: pipeline от входа логов до минимальной обработки.

- Infra:
  - Инициализация Laravel-проекта + Docker.
  - Настройка queue-worker.
- Multi-tenant basics:
  - `projects`, `api_keys` модели и миграции.
  - Middleware `ApiKeyAuth`.
- Ingestion:
  - Endpoint `/api/logs` (валидация + очередь).
  - Job `ProcessLogBatchJob` (базовая логика).
- Deployment Context:
  - `deployments` модель/таблица.
  - Endpoint `/api/deployments`.
  - `LogContextEnricher` (простая привязка по времени).
- Filtering:
  - `LogFilter` базовый (level + health-check).

**Результат:**  
Можно отправить логи и деплои, они проходят через очередь, enriched и фильтруются (без реального форвардинга).

---

### Итерация 2 (Неделя 2): Forwarding + статистика + агрегаты

- Forwarding:
  - `downstream_endpoints` модель.
  - `LogForwarder` (http + file).
  - Интеграция в `ProcessLogBatchJob`.
- Aggregation:
  - Redis-счётчики повторяющихся ошибок.
  - `ErrorAggregator` + `aggregated_errors`.
- Stats:
  - `project_hourly_stats` миграция/модель.
  - `StatsRecorder` (Redis).
  - `HourlyStatsFlushJob` + cron.
- (Опционально) `log_events` для хранения обогащённых событий.

**Результат:**  
Логи реально уходят в downstream. Есть статистика вход/выход/фильтрация, первые агрегаты ошибок.

---

### Итерация 3 (Неделя 3): Dashboard + админка + демо

- Admin/Web:
  - Web-auth (User, login).
  - CRUD для проектов и API-ключей.
  - Настройка downstream endpoint в UI.
- Dashboard:
  - `ProjectDashboardController` + Blade-вьюхи.
  - Графики incoming/outgoing/filtered.
  - % экономии.
  - Блок “Ошибки вокруг деплоя” + список деплоев.
- Demo:
  - Seed-скрипт демо-проекта.
  - README с примерами:
    - curl-примеры отправки логов;
    - пример деплой-хука.

**Результат:**  
Есть end-to-end демо: логи → фильтрация → контекст деплоя → downstream → дашборд с экономией.

---

## 7. Финальный обзор демо-ценности Auto-Context MVP

**Возможности MVP:**

- Приём JSON-логов по HTTP с API-ключом (одиночные и батчи).
- Регистрация деплоев и привязка логов к релизам (deployment_id, version, deployment_related).
- Фильтрация шума:
  - DEBUG/TRACE.
  - health-check-и и служебные запросы.
  - повторяющиеся ошибки по hash с порогом.
- Форвардинг очищенного потока в downstream (HTTP или файл).
- Подсчёт и отображение:
  - входящих vs исходящих событий;
  - количества отфильтрованных;
  - % экономии;
  - ошибок, связанных с последним деплоем.
- Простейшая админка:
  - проекты;
  - API-ключи;
  - downstream-настройки.

**Демо для пилотного клиента (DevOps/SRE):**

1. Создаём “Project A” в админке, выдаём API-ключ.
2. Показываем пример конфигурации:
   - endpoint `/api/logs`;
   - заголовок `X-Api-Key`.
3. Подключаем CI/CD-хук на `POST /api/deployments`.
4. Генерируем нагрузку:
   - много DEBUG-логов и health-check запросов;
   - несколько ошибок до и после деплоя.
5. На дашборде:
   - видим входящие vs исходящие события и % экономии.
   - видим рост `deployment_related` ошибок после релиза.
   - смотрим агрегированные ошибки конкретного деплоя.

**Демо для инвестора:**

- До Auto-Context: “сырые логи → дорогой Datadog/Splunk”.  
- После Auto-Context:
  - Auto-Context сидит **перед** Datadog/Splunk.
  - Урезает объём логов (например, -40%) за счёт фильтрации.
  - Добавляет контекст деплоев (видно, что именно релиз X сломал прод).
  - Можно показать:
    - график “было/стало”;
    - цифры экономии;
    - простой UI, который DevOps реально будет использовать.

**Осязаемая ценность MVP:**

- **Экономия денег**: меньше логов уходит в дорогой observability.
- **Ускорение расследования**: быстрый фокус на логах после деплоя.
- **Минимальная интеграция**: один HTTP endpoint + деплой-хук, внедрение за день.

Auto-Context MVP — уже “живой” продукт, который можно:
- показать пилотным DevOps/SRE,
- использовать как основу для питча инвесторам,
- дальше развивать (настраиваемые правила, multi-region, интеграции, UI-улучшения).
