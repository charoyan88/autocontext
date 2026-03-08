# Open Source Release Checklist

## Goal

Prepare the first public repository state with a clear local startup path, accurate documentation, and no misleading private or experimental defaults.

## Safe to include now

- Core Laravel app, dashboard, API ingest, deployments, filtering, aggregation, and downstream forwarding
- Public docs and repo metadata:
  - `README.md`
  - `CONTRIBUTING.md`
  - `SECURITY.md`
  - `LICENSE`
- Local Docker Compose workflow on `http://localhost:8080`
- Current automated tests that validate auth, validation, filtering, forwarding, and stats flows
- ClickHouse direct-write support as optional and disabled by default

## Include only if documented as experimental

- `app/Services/ClickhouseClient.php`
- `app/Services/ClickhouseLogWriter.php`
- `app/Console/Commands/ClickhouseSetupCommand.php`
- `config/clickhouse.php`
- `database/clickhouse/schema.sql`
- `docker/clickhouse/`
- `docker/kafka-connect/`
- `scripts/register_clickhouse_sink.sh`

These pieces are not part of the default quick start and should not be presented as production-ready.

## Keep out of the first public commit

- Personal walkthroughs, local screenshots, or machine-specific notes
- Incomplete feature branches presented as stable functionality
- Unverified marketing claims that are not backed by the current codebase

## Before pushing

- Make sure `git status` is intentional
- Run tests in an environment with `pdo_pgsql` available
- Re-read `README.md` from the perspective of a first-time contributor
- Confirm that optional features are labeled optional everywhere they appear
