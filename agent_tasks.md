# Agent Tasks

## Iteration 1 – Core ingestion, auth, deployments
- Infra: initialize Laravel project, add Dockerfile (php-fpm) and docker-compose with nginx, Postgres, Redis, queue worker.
- Infra: configure queue worker container (artisan queue:work / supervisor) and validate Redis queue processing.
- Data: create migrations/models for `projects`, `api_keys`, `deployments`.
- Auth: implement `ApiKeyAuth` middleware, plug into API routes, store resolved `project` in request.
- Ingestion: build `POST /api/logs` accepting single log or `{events: []}`, minimal validation, enqueue `ProcessLogBatchJob`, return `202` + accepted count.
- Queue: implement `ProcessLogBatchJob ($projectId, array $events)` that currently just logs/prints processed batch.
- Deployments: create `POST /api/deployments` endpoint with validation, storing deployments tied to authenticated project.
- Context: scaffold `LogContextEnricher` to attach deployment info (basic window logic placeholder).
- Filtering: add `LogFilter` service using config default (drop DEBUG/TRACE + health endpoints).

## Iteration 2 – Forwarding, stats, aggregation
- Data: migrations/models for `downstream_endpoints`, `aggregated_errors`, `log_events?` (optional), `project_hourly_stats`.
- Forwarding: implement `LogForwarder` (HTTP + file) and integrate into `ProcessLogBatchJob`.
- Downstream UI: admin form to configure endpoint (type, URL, headers/config stored in JSON).
- Filtering: Redis-based duplicate suppression (error hash, per-minute counter, threshold from config).
- Aggregation: `ErrorAggregator` service with Redis counters + DB upsert for aggregated errors.
- Stats: `StatsRecorder` service incrementing Redis hourly counters (incoming/outgoing/filtered/deployment errors).
- Jobs: `HourlyStatsFlushJob` to upsert counts into `project_hourly_stats` and cleanup Redis keys.
- Storage: optional persistence of enriched log events (recent window) for debugging.

## Iteration 3 – Dashboard, admin, demo
- Auth: basic web auth (User model, login form), protect admin routes.
- Admin: CRUD for projects (list/create/update/disable) and API keys (list, generate random key, deactivate/delete).
- Downstream: web form to manage endpoint config per project.
- Dashboard: `ProjectDashboardController`, Blade views `/projects`, `/projects/{id}/dashboard` with charts (incoming/outgoing/filtered, % savings) using Chart.js/Livewire.
- Deployment insights: show recent deployments with related error counts, highlight deployment-related errors metric.
- Demo tooling: artisan `demo:seed` command to create sample project, API key, downstream config, sample deployments/logs; README instructions for running via Docker and demonstrating API usage.

## Cross-cutting
- Redis + PostgreSQL config shared across services/jobs.
- Error handling/logging conventions (e.g., mark forward_failed, stats increments).
- Documentation updates alongside features (README sections for API usage, dashboard, seeding).
