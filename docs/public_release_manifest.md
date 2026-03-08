# Public Release Manifest

## Commit in the first public release

### Repo metadata and onboarding

- `.env.example`
- `README.md`
- `CONTRIBUTING.md`
- `SECURITY.md`
- `LICENSE`
- `composer.json`
- `start.sh`
- `docker-compose.yml`
- `docker/nginx/default.conf`

### Core app and public UI cleanup

- `config/app.php`
- `database/seeders/DemoSeeder.php`
- `resources/views/docs.blade.php`
- `resources/views/layouts/navigation.blade.php`
- `resources/views/welcome.blade.php`

### Tests and fixes that support the stable flow

- `tests/TestCase.php`
- `tests/Feature/ApiKeyManagementTest.php`
- `tests/Feature/ApiKeyAuthTest.php`
- `tests/Feature/HourlyStatsFlushJobTest.php`
- `tests/Feature/LogIngestValidationTest.php`
- `tests/Feature/StatsFlushEndpointTest.php`
- `tests/Unit/FileAdapterTest.php`
- `tests/Unit/LogFilterTest.php`

### Optional but already wired into the current app flow

- `app/Jobs/ProcessLogBatchJob.php`
- `app/Providers/AppServiceProvider.php`
- `app/Console/Commands/ClickhouseSetupCommand.php`
- `app/Services/ClickhouseClient.php`
- `app/Services/ClickhouseLogWriter.php`
- `config/clickhouse.php`
- `database/clickhouse/schema.sql`
- `docker/clickhouse/`

Reason:
`ProcessLogBatchJob` already depends on `ClickhouseLogWriter`, so direct ClickHouse support is part of the current code path even though it is disabled by default via `CLICKHOUSE_ENABLED=false`.

## Keep for the second stage

- `docker/kafka-connect/`
- `scripts/register_clickhouse_sink.sh`

Reason:
Kafka Connect is not part of the default startup flow, is not covered by the local quick start, and should not be presented as a first-class public feature yet.

## Already removed from public release scope

- `docs/walkthrough_2026_01_17.md`

Reason:
It contained local machine-specific content and did not belong in the public repository surface.

## Preconditions before push

- Run tests in an environment with `pdo_pgsql`
- Make sure the first public commit message describes the project as MVP plus optional ClickHouse support
- Keep Kafka Connect out of the initial README and release notes unless it gets its own verified setup path
