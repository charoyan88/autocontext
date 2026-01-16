1. Архитектура MVP
1.1. Основные компоненты

1) HTTP API для логов

Endpoint: POST /api/logs

Формат: JSON (один лог или батч { events: [...] })

Авторизация: X-Api-Key: <key>

Компоненты:

App\Http\Controllers\Api\LogIngestController

Middleware ApiKeyAuth (по X-Api-Key)

DTO/сервис нормализации логов LogNormalizer

Очередь: публикация ProcessLogBatchJob

2) HTTP API для деплоев

Endpoint: POST /api/deployments

Авторизация: тот же X-Api-Key

Компоненты:

App\Http\Controllers\Api\DeploymentController

Модель Deployment

Валидация: version, started_at, finished_at?, region?, metadata?

3) Слой авторизации / API-ключей

Таблица api_keys

Middleware:

App\Http\Middleware\ApiKeyAuth:

Ищет ApiKey по key, is_active = true

Вешает в request объект project (или в контейнере)

Для веб-UI — отдельная простая аутентификация (users + auth:web).

4) Очереди и Jobs

Основной пайплайн для одного батча логов:

ProcessLogBatchJob

вход: project_id, массив сырых событий

для каждого события:

EnrichLogEventJob (или внутренний сервис)

FilterLogEventJob (можно как шаг внутри одного Job)

ForwardLogEventJob (успешные → downstream)

UpdateStatsJob (инкремент счетчиков, обновление ошибок)

Для MVP скорее:

ProcessLogBatchJob:

в коде:

LogContextEnricher::enrich($event)

LogFilter::shouldDrop($event)

если не дропнут:

LogForwarder::forward($event)

StatsRecorder::record($event, incoming=true, outgoing=true/false)

если дропнут:

StatsRecorder::record(incoming=true, outgoing=false)

Т.е. можно оставить один Job, а остальные как сервисы — проще для MVP.

5) Подсистема статистики

Сервисы:

StatsRecorder:

инкрементирует счетчики в Redis: ключи вида
stats:{project_id}:{YYYYMMDDHH}:incoming / outgoing / filtered / deployment_errors

HourlyStatsFlushJob (cron):

раз в N минут/час: считывает Redis-ключи, делает upsert в PostgreSQL project_hourly_stats

Аггрегированные ошибки:

ErrorAggregator:

считает error_hash (по message + stack + type)

держит счётчик в Redis, периодически сбрасывает в aggregated_errors.

6) Веб-дашборд

Технология: Laravel Blade + немного Alpine/Livewire/Chart.js (MVP)

Компоненты:

App\Http\Controllers\Web\ProjectDashboardController

Вьюхи:

/projects — список проектов

/projects/{id} — дашборд

Отображает:

Входящие/исходящие за период (по часам/дням)

% экономии

Ошибки, связанные с последним деплоем

Список последних деплоев + связанная статистика

7) Админка / управление проектами и API-ключами

Очень простой CRUD:

ProjectController (web)

ApiKeyController (web)

Функции:

создать проект

сгенерировать API-ключ

отключить ключ

настроить downstream endpoint (URL, тип, headers)

8) Downstream форвардинг

Модель DownstreamEndpoint:

тип: http, s3, file (в MVP можно оставить http + локальный файл)

Сервис:

LogForwarder:

для http: Guzzle POST в endpoint_url

для file: запись в локальный файл/rotating log

При ошибках:

помечаем event как forward_failed и увеличиваем отдельный счетчик.

1.2. Data-flow

Путь логов:

Клиентское приложение:

шлёт POST /api/logs с X-Api-Key и JSON (1 или батч событий)

LogIngestController:

авторизует по API-ключу → получает project_id

валидирует базовую схему (timestamp, level, message, context?)

публикует ProcessLogBatchJob($project_id, $events)

ProcessLogBatchJob (worker из очереди Redis):

для каждого события:

StatsRecorder: incoming++ (Redis)

LogContextEnricher:

ищет релевантный Deployment:

последний deployment по проекту с started_at <= event.timestamp

при наличии finished_at — проверка, что event.timestamp <= finished_at

ставит поля:

deployment_id

deployment_version

deployment_related = true/false (например, в окне N минут после деплоя)

service, region (из event или project config)

LogFilter:

правила:

level in [DEBUG, TRACE] → дроп

health-check пути GET /health, GET /metrics → дроп

повторяющиеся ошибки:

вычисляем error_hash

Redis INCR по ключу err:{project_id}:{hash}:{minute}

если count > threshold → дроп (но считаем как incoming)

Если не дропнут:

LogForwarder::forward($event):

отправляем в downstream

StatsRecorder: outgoing++

если deployment_related = true и level in [ERROR, FATAL] → deployment_errors++

(опционально) сохраняем в log_events (только обогащённый вид)

Если дропнут:

StatsRecorder: filtered++

при ошибках форвардинга:

StatsRecorder: outgoing_failed++ (опционально)

HourlyStatsFlushJob:

раз в час сбрасывает Redis-счётчики в project_hourly_stats

Web-дэшборд:

читает project_hourly_stats, deployments, aggregated_errors для графиков.

Путь деплоев:

CI/CD или деплой-сервис:

шлёт POST /api/deployments с X-Api-Key

тело: { version, started_at, finished_at?, region?, metadata? }

DeploymentController:

авторизует по API-ключу → project_id

создаёт запись deployments

При обработке логов:

LogContextEnricher использует deployments данного проекта для привязки к событиям.

2. Модель данных (PostgreSQL / Laravel Models)
2.1. projects

id (bigint, PK)

name (string)

slug (string, unique)

status (enum: active, inactive)

default_region (string, nullable)

created_at, updated_at

Model: App\Models\Project

2.2. api_keys

id (bigint, PK)

project_id (FK → projects)

key (string, unique, индекс для поиска по заголовку)

is_active (boolean)

created_at, updated_at

last_used_at (timestamp, nullable)

description (string, nullable)

Model: App\Models\ApiKey

2.3. deployments

id (bigint, PK)

project_id (FK → projects, индекс)

version (string) — например, git SHA / tag

environment (string, nullable — prod, staging)

region (string, nullable)

started_at (timestamp)

finished_at (timestamp, nullable)

metadata (jsonb, nullable)

индексы:

(project_id, started_at DESC)

Model: App\Models\Deployment

2.4. downstream_endpoints (per project)

id (bigint, PK)

project_id (FK → projects)

type (enum: http, file, s3 — в MVP можно ограничиться http и file)

endpoint_url (string, nullable для file)

config (jsonb) — headers, auth, путь к файлу и т.д.

is_active (boolean)

created_at, updated_at

Model: App\Models\DownstreamEndpoint

2.5. log_events (опционально, хранение обогащённых событий)

Для MVP можно хранить только последние N дней/ограниченный volume:

id (bigint, PK)

project_id (FK)

deployment_id (FK, nullable)

timestamp (timestamp)

level (string)

message (text)

service (string, nullable)

region (string, nullable)

payload (jsonb) — оригинальный + обогащенный контекст

deployment_related (boolean)

индексы:

(project_id, timestamp DESC)

(project_id, deployment_id)

Model: App\Models\LogEvent

2.6. aggregated_errors

id (bigint, PK)

project_id (FK)

error_hash (string, индекс)

last_message (text)

level (string)

last_seen_at (timestamp)

count_total (bigint)

count_since_last_deploy (bigint)

last_deployment_id (bigint, nullable)

sample_event (jsonb, nullable)

Model: App\Models\AggregatedError

2.7. project_hourly_stats

id (bigint, PK)

project_id (FK)

hour_ts (timestamp без времени минут/секунд, начало часа)

incoming_count (bigint)

outgoing_count (bigint)

filtered_count (bigint)

deployment_error_count (bigint)

уникальный индекс: (project_id, hour_ts)

Model: App\Models\ProjectHourlyStat

(Можно добавить daily-агрегацию позже.)

3. Эпики

Эпик: Ingestion & Auth (Подключение источников логов)

Goal: DevOps может отправлять JSON-логи в единый endpoint с API-ключом.

Компоненты:

LogIngestController, ApiKeyAuth middleware

Project, ApiKey модели

ProcessLogBatchJob, очередь

Эпик: Deployment Context Engine

Goal: Привязка логов к деплою и маркировка событий как связанных с последним деплоем.

Компоненты:

DeploymentController, Deployment модель

LogContextEnricher сервис

Частично aggregated_errors

Эпик: Filtering & Aggregation

Goal: Удалять шум и агрегировать повторяющиеся ошибки.

Компоненты:

LogFilter сервис

Использование Redis для детекции повторов

aggregated_errors таблица + ErrorAggregator

Эпик: Forwarding

Goal: Отправка очищенного потока логов в downstream (HTTP/file) надёжно и прозрачно.

Компоненты:

DownstreamEndpoint модель

LogForwarder сервис

Настройки в веб-панели

Эпик: Dashboard & Analytics

Goal: Показать DevOps/SRE экономию, объём логов и связь с деплоем в простом UI.

Компоненты:

ProjectHourlyStat модель

StatsRecorder, HourlyStatsFlushJob

ProjectDashboardController, Blade-шаблоны, графики

Эпик: Multi-tenant basics (Projects & API Keys Management)

Goal: Создание/управление проектами, API-ключами и downstream настройками.

Компоненты:

ProjectController, ApiKeyController, DownstreamEndpointController

Web auth (User модель), базовая админка

Эпик: Infrastructure & DevOps

Goal: Запуск всего стека через Docker, готовность к развёртыванию.

Компоненты:

docker-compose.yml, Dockerfile

Makefile/скрипты

env-конфигурация для PostgreSQL/Redis

4. User Stories + Acceptance Criteria
Эпик: Ingestion & Auth

Story 1
Как DevOps, я хочу отправлять JSON-логи в единый endpoint с API-ключом, чтобы не менять сильно существующую систему логирования.

Acceptance:

Есть endpoint POST /api/logs.

Авторизация по заголовку X-Api-Key.

При неверном/отключённом ключе — 401/403.

Валидные события кладутся в очередь, ответ — 202 Accepted.

Story 2
Как DevOps, я хочу отправлять батч логов, чтобы сократить overhead на сеть и HTTP.

Acceptance:

Endpoint принимает как один объект, так и { events: [...] }.

При отправке батча все валидные события попадают в очередь.

В ответе — count принятых событий.

Эпик: Deployment Context Engine

Story 3
Как SRE, я хочу регистрировать деплой через API, чтобы система знала, какие ошибки связаны с конкретным релизом.

Acceptance:

Есть endpoint POST /api/deployments.

Поля: version, started_at, finished_at?, environment?, region?, metadata?.

Запись появляется в таблице deployments с привязкой к project.

Доступ по API-ключу проекта.

Story 4
Как SRE, я хочу видеть, какие логи были связаны с последним деплоем, чтобы быстрее находить причины инцидентов.

Acceptance:

При обработке логов события получают:

deployment_id, deployment_version, deployment_related.

Лог считается deployment_related, если:

его timestamp попадает в окно N минут после started_at последнего деплоя (или до finished_at, если задан).

На дашборде есть показатель “Ошибки, связанные с последним деплоем” за период.

Эпик: Filtering & Aggregation

Story 5
Как DevOps, я хочу фильтровать DEBUG/TRACE и health-check запросы, чтобы не тратить деньги на шум.

Acceptance:

Конфиг по умолчанию: уровни DEBUG, TRACE — дроп.

Запросы с путями /health, /ping, /metrics — дроп.

Отфильтрованные события не отправляются в downstream, но считаются как входящие.

Story 6
Как SRE, я хочу, чтобы повторяющиеся ошибки агрегировались, а не слались миллионами одинаковых логов.

Acceptance:

Для ошибок (level >= ERROR) считается error_hash.

Если за минуту по этому hash уже отправлено > N событий, последующие в этой минуте дропаются.

В aggregated_errors обновляется счётчик и last_seen_at.

Эпик: Forwarding

Story 7
Как DevOps, я хочу перенаправлять очищенные логи в существующую систему (например, HTTP endpoint), чтобы интеграция была минимальной.

Acceptance:

Есть настройка downstream endpoint на уровне проекта (тип: http).

Все неотфильтрованные события отправляются POST-запросом на этот endpoint.

При ошибке downstream система логирует failure и увеличивает соответствующий счётчик.

Эпик: Dashboard & Analytics

Story 8
Как Platform-инженер, я хочу видеть, насколько уменьшился объём отправляемых логов, чтобы оценить экономию.

Acceptance:

На странице проекта видны:

Входящие события за выбранный период.

Исходящие события.

% экономии = (1 - outgoing/incoming) * 100.

Графики по часам/дням на основе project_hourly_stats.

Story 9
Как SRE, я хочу видеть статистику ошибок, связанных с деплоем, чтобы быстро понять, “сломал ли релиз прод”.

Acceptance:

На дашборде есть:

Количество deployment_related ошибок за период.

Список последних деплоев и сумма ошибок вокруг каждого.

Эпик: Multi-tenant basics

Story 10
Как владелец платформы, я хочу создавать проекты и API-ключи через веб-панель, чтобы быстро подключать новые сервисы.

Acceptance:

Страница списка проектов.

Форма создания/редактирования проекта.

Внутри проекта — список API-ключей, CRUD для ключей (генерация, отключение).

Эпик: Infrastructure & DevOps

Story 11
Как DevOps, я хочу запускать Auto-Context одной командой через Docker, чтобы быстро развернуть MVP для теста.

Acceptance:

Есть docker-compose.yml c сервисами:

app (php-fpm)

nginx

postgres

redis

queue-worker

README с инструкцией запуска.

Все миграции и очереди поднимаются командой docker-compose up + artisan migrate.

5. Backlog задач (по эпику)

Буду писать в формате:

[Эпик] Task: Описание

Acceptance

Dependencies

Эпик: Infrastructure & DevOps

Task: Инициализация Laravel-проекта + базовый Docker

Acceptance:

Репозиторий с Laravel.

Dockerfile для php-fpm.

docker-compose.yml с nginx, app, postgres, redis.

Dependencies: нет.

Task: Настройка queue-worker в Docker

Acceptance:

Отдельный сервис queue-worker (supervisor или artisan queue:work).

Очередь redis работает, тестовый job обрабатывается.

Dependencies: базовый Docker.

Эпик: Multi-tenant basics

Task: Миграции и модели projects, api_keys

Acceptance:

Миграции созданы, модели настроены, связи Project hasMany ApiKey.

Task: Middleware ApiKeyAuth

Acceptance:

Создан middleware, добавлен в API-роуты.

При валидном X-Api-Key доступ разрешён, в запросе доступен project.

При неверном — 401.

Task: Web-auth для админки (простой login)

Acceptance:

Модель User, миграция.

Авторизация через auth:web.

Middleware auth для web-роутов.

Task: CRUD для проектов

Acceptance:

Список проектов.

Создание/редактирование/деактивация.

Task: CRUD для API-ключей проекта

Acceptance:

Список ключей внутри проекта.

Создание (генерация случайного ключа).

Деактивация/удаление.

Эпик: Ingestion & Auth

Task: Endpoint POST /api/logs + валидация

Acceptance:

Принимает одиночный лог и батч events.

Проверяет минимальные поля (timestamp, level, message).

Возвращает 202 и количество принятых событий.

Dependencies: Middleware ApiKeyAuth.

Task: Job ProcessLogBatchJob

Acceptance:

Создан Job с сигнатурой $projectId, array $events.

Публикация в очередь из контроллера.

Обработка выполняется воркером (пока без enrichment/filtering — просто лог в файл).

Эпик: Deployment Context Engine

Task: Миграция/модель deployments

Acceptance:

Таблица как в модели данных.

Модель Deployment с отношением к Project.

Task: Endpoint POST /api/deployments

Acceptance:

Валидация входа.

Создаёт запись, возвращает её JSON.

Привязан к проекту через API-ключ.

Task: Сервис LogContextEnricher

Acceptance:

Метод enrich($project, $event):

Находит релевантный deployment.

Добавляет в event: deployment_id, deployment_version, deployment_related.

Unit-тест: проверка логики окна N минут.

Эпик: Filtering & Aggregation

Task: Сервис LogFilter

Acceptance:

Метод shouldDrop($event):

Дропает при level in ["DEBUG","TRACE"].

Дропает при path in ["/health","/metrics","/ping"].

Конфиг по умолчанию берётся из config/log_filter.php.

Task: Redis-счетчики для повторяющихся ошибок

Acceptance:

error_hash считается по message+stack.

Redis ключ err:{project_id}:{hash}:{YYYYMMDDHHMM} INCR.

Если count > threshold, shouldDrop возвращает true.

threshold в конфиге.

Task: Миграция/модель aggregated_errors

Acceptance:

Таблица по модели данных.

Модель c методом upsert по (project_id, error_hash).

Task: Сервис ErrorAggregator

Acceptance:

Метод record($projectId, $event, $deploymentId?):

обновляет/создаёт запись в aggregated_errors.

обновляет last_message, last_seen_at, счётчики.

Эпик: Forwarding

Task: Миграция/модель downstream_endpoints

Acceptance:

Таблица + модель.

Связь Project hasOne DownstreamEndpoint.

Task: Сервис LogForwarder (HTTP + file)

Acceptance:

Для http:

отправляет POST JSON на указанный URL.

учитывает доп. headers из config.

Для file:

пишет строку JSON в файл (например, storage/logs/downstream_{project}.log).

Обрабатывает ошибки (исключения) и сигнализирует об этом.

Task: Интеграция LogForwarder в ProcessLogBatchJob

Acceptance:

В обработке событий фильтруемые не отсылаются.

Неотфильтрованные — отправляются через сервис.

Task: UI для настройки downstream endpoint

Acceptance:

Форма в админке проекта: выбрать тип endpoint, задать URL/config.

Валидация, сохранение.

Эпик: Dashboard & Analytics

Task: Миграция/модель project_hourly_stats

Acceptance:

Таблица + уникальный индекс (project_id, hour_ts).

Task: Сервис StatsRecorder

Acceptance:

Методы:

recordIncoming($projectId)

recordOutgoing($projectId)

recordFiltered($projectId)

recordDeploymentError($projectId)

Все операции — инкременты Redis-ключей по часам.

Task: Job HourlyStatsFlushJob (cron)

Acceptance:

Читает Redis-ключи за последние M часов.

Делаает upsert в project_hourly_stats.

После успешной записи удаляет соответствующие ключи.

Task: Web-дэшборд проекта

Acceptance:

Страница /projects/{id}/dashboard.

Период по умолчанию — последние 24 часа/7 дней.

Показатели: incoming, outgoing, filtered, % экономии, deployment_error_count.

Простейшие графики (Chart.js или аналог).

Эпик: UX & Demo подготовка (микро)

Task: Упрощённый список последних деплоев на дашборде

Acceptance:

Таблица последних N деплоев (version, started_at, количество ошибок вокруг деплоя).

Task: Seed-скрипт для демо-проекта

Acceptance:

Команда php artisan demo:seed создаёт:

тестовый проект

API-ключ

тестовый downstream (file)

пару деплоев

Документация в README.

6. Roadmap MVP на 2–3 недели

Ориентируемся на 2–3 спринта по ~1 неделе.

Итерация 1 (Неделя 1): Core ingestion + deployments + basic filtering

Фокус: pipeline от входа логов до минимальной обработки (без форвардинга и UI).

Infra:

Инициализация Laravel-проекта + Docker.

Настройка queue-worker.

Multi-tenant basics:

projects, api_keys модели и миграции.

Middleware ApiKeyAuth.

Ingestion:

Endpoint /api/logs (валидация + очередь).

Job ProcessLogBatchJob (пока просто логирует).

Deployment Context:

deployments модель/таблица.

Endpoint /api/deployments.

LogContextEnricher (заглушка/простая логика привязки по времени).

Filtering:

LogFilter базовый (level + health-check).

Результат недели:
Можно отправить логи и деплои, они проходят через очередь, enriched и фильтруются (но ещё не форвардятся), можно смотреть в логах/DB.

Итерация 2 (Неделя 2): Forwarding + статистика + хранение агрегатов

Forwarding:

downstream_endpoints модель.

LogForwarder (http + file).

Интеграция в ProcessLogBatchJob.

Aggregation:

Redis-счётчики повторяющихся ошибок + ErrorAggregator + aggregated_errors.

Stats:

project_hourly_stats миграция/модель.

StatsRecorder (Redis).

HourlyStatsFlushJob + cron.

Простое хранение log_events (по необходимости).

Результат недели:
Логи реально уходят в downstream. Есть статистика вход/выход/фильтрация и первые агрегаты ошибок в БД.

Итерация 3 (Неделя 3): Dashboard + админка + демо

Admin/Web:

Web-auth (User, login).

CRUD для проектов и API-ключей.

Настройка downstream endpoint в UI.

Dashboard:

ProjectDashboardController + Blade-вьюхи.

Графики incoming/outgoing/filtered.

% экономии.

Блок “Ошибки вокруг деплоя” + список последних деплое⁠в.

Demo:

Seed-скрипт демо-проекта.

README с сценариями:

как отправить логи (curl, пример кода),

как посмотреть дашборд.

Результат недели:
Есть end-to-end демо: кидаешь логи и деплои → смотришь на графики и экономию, downstream получает очищенный поток.

7. Финальный обзор демо-ценности

Что умеет MVP Auto-Context:

Принимает JSON-логи по HTTP с авторизацией по API-ключу (batch-friendly).

Регистрирует деплои и привязывает к ним события (deployment_id, version, deployment_related).

Фильтрует шум:

DEBUG/TRACE.

health-check-и и подобные запросы.

повторяющиеся ошибки выше порога (по hash).

Отправляет очищенный поток в downstream endpoint (HTTP или файл).

Считает и показывает:

входящие vs исходящие события;

количество отфильтрованных;

% экономии;

количество ошибок, связанных с последним деплоем.

Даёт простую админку:

проекты;

API-ключи;

настройки downstream.

Как показать пилотному клиенту (DevOps/SRE):

Сценарий демо:

Создаём для него “Project A” в админке, выдаём API-ключ.

Показываем пример конфигурации в их приложении:

куда слать логи (/api/logs),

где вставить API-ключ.

Показываем, как CI/CD шлёт POST /api/deployments при релизе.

Генерируем нагрузку:

много DEBUG-логов + health-check запросы.

несколько разных ошибок до и после деплоя.

На дашборде:

видим входящие vs исходящие (и сразу % экономии).

видим, что после деплоя выросли deployment_related ошибки.

открываем агрегированные ошибки конкретного деплоя.

Как показать инвестору:

Одно простое слайд/демо:

На вход — график “все логи” (большой поток).

На выход — “очищенный, обогащённый поток” + % экономии (например, -40% объёма).

Плашка: “Ошибки последнего деплоя: X” — быстрый сигнал, который сейчас DevOps собирает вручную из разных систем.

Объяснение: Auto-Context сидит перед Datadog/Splunk и уменьшает чек, давая при этом больше контекста про деплои.

Осязаемая ценность уже на этом MVP:

Экономия денег: меньше логов уходит в дорогие SaaS (Datadog/Splunk).

Скорость расследования инцидентов: “всё, что сломалось после последнего деплоя” видно сразу.

Минимальная интеграция: одно HTTP-API и деплой-hook — можно подключить за день.
