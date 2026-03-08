# Experimental Kafka Connect

## Status

This is an experimental integration path for sending log data into ClickHouse through Kafka Connect. It is not part of the default quick start and should not be treated as production-ready project setup.

## Included assets

- `docker/kafka-connect/Dockerfile`
- `scripts/register_clickhouse_sink.sh`

## What it does

- builds a Kafka Connect image with the ClickHouse sink connector installed
- registers a `clickhouse-sink` connector against a Kafka Connect instance
- targets the `logs_raw` ClickHouse table

## Assumptions

- you already have Kafka, Kafka Connect, and ClickHouse running
- the ClickHouse schema from `database/clickhouse/schema.sql` is already applied
- connector registration is performed against `KAFKA_CONNECT_URL`

## Environment variables

The registration script uses:

- `CONNECT_URL`
- `KAFKA_TOPIC`
- `CLICKHOUSE_HOST`
- `CLICKHOUSE_PORT`
- `CLICKHOUSE_DATABASE`
- `CLICKHOUSE_USER`
- `CLICKHOUSE_PASSWORD`

## Manual registration

```bash
./scripts/register_clickhouse_sink.sh
```

## Not included

- a full Compose stack for Kafka
- end-to-end verification in the default local workflow
- release guarantees beyond repository-level experimentation
