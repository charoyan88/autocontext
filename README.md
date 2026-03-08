# Auto-Context

Deployment-aware log filtering and context enrichment for self-hosted observability.

Auto-Context helps teams understand which deployment caused which errors by linking logs to deploys, filtering low-value noise, aggregating repeated failures, and forwarding high-signal events to downstream tools.

Built for teams who want a lightweight, self-hosted observability layer between their apps and external logging systems.

## Status

- MVP, actively evolving
- Core local flow works with Docker Compose
- ClickHouse and Kafka-related files are present, but they are optional and not part of the default quick start

## Features

- API key authentication for project-scoped ingest
- Deployment tracking via `POST /api/deployments`
- Log ingestion via `POST /api/logs`
- Deployment context enrichment during async processing
- Noise filtering for low-value logs and health checks
- Error aggregation for repeated failures
- Downstream forwarding to file, HTTP, and Sentry-style endpoints
- Dashboard for projects, deployments, API keys, and stats

## Stack

- Laravel 12 / PHP 8.2+
- PostgreSQL
- Redis
- Nginx
- Blade + Tailwind + Vite
- Docker Compose

## Quick Start

### Prerequisites

- Docker with Compose support

### Start the project

```bash
git clone https://github.com/charoyan88/autocontext.git
cd autocontext
./scripts/start_mvp.sh
```

The app will be available at `http://localhost:8080`.

### Alternative startup script

```bash
./start.sh
```

### Demo access

- Dashboard: `http://localhost:8080/login`
- Email: `admin@auto-context.local`
- Password: `password`
- API base URL: `http://localhost:8080/api`

## Local Services

The default `docker-compose.yml` brings up:

- `app`
- `queue-worker`
- `scheduler`
- `nginx`
- `vite`
- `postgres`
- `redis`

Nginx is published on host port `8080`.

## API Examples

### Send a single log event

```bash
curl -X POST http://localhost:8080/api/logs \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "timestamp": "2026-01-17T12:00:00+00:00",
    "level": "ERROR",
    "message": "Database connection failed",
    "context": {
      "exception": "PDOException",
      "file": "Database.php",
      "line": 42
    }
  }'
```

### Send a batch

```bash
curl -X POST http://localhost:8080/api/logs \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "events": [
      {"timestamp": "2026-01-17T12:00:00+00:00", "level": "INFO", "message": "Request started"},
      {"timestamp": "2026-01-17T12:00:01+00:00", "level": "ERROR", "message": "Request failed"}
    ]
  }'
```

### Track a deployment

```bash
curl -X POST http://localhost:8080/api/deployments \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "version": "v1.2.0",
    "environment": "production",
    "region": "us-east-1",
    "started_at": "2026-01-17T12:00:00+00:00"
  }'
```

## Configuration Notes

Copy `.env.example` to `.env` if you are not using the startup scripts.

Important defaults:

```env
APP_URL=http://localhost:8080
DB_HOST=postgres
DB_DATABASE=auto_context
DB_USERNAME=auto
DB_PASSWORD=secret
REDIS_HOST=redis
QUEUE_CONNECTION=redis
```

Optional integrations:

- `CLICKHOUSE_ENABLED=false` by default
- ClickHouse environment variables are included for experimentation but the default Compose flow does not require a ClickHouse container
- Kafka-related env vars and `docker/kafka-connect/` are experimental and not part of the default local startup path yet
- Downstream HTTP, Sentry, and file targets should be treated as trusted-admin integrations

Experimental Kafka Connect notes: see [docs/experimental_kafka_connect.md](docs/experimental_kafka_connect.md).

## Tests

Run the test suite inside the app container:

```bash
docker compose exec app php artisan test
```

Run a specific test file:

```bash
docker compose exec app php artisan test tests/Feature/LogBatchProcessingTest.php
```

## Development Notes

- Queue processing runs in the `queue-worker` container
- Scheduled stat flushing runs in the `scheduler` container
- Frontend assets are handled by the `vite` container
- Nginx is configured generically for local use and does not require a specific domain name

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for the expected local workflow.

## Security

See [SECURITY.md](SECURITY.md) for the current trust model and disclosure guidance.

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE).
