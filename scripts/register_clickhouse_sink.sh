#!/usr/bin/env bash
set -euo pipefail

CONNECT_URL="${CONNECT_URL:-http://localhost:8083}"
TOPIC="${KAFKA_TOPIC:-logs}"
CLICKHOUSE_HOST="${CLICKHOUSE_HOST:-clickhouse}"
CLICKHOUSE_PORT="${CLICKHOUSE_PORT:-8123}"
CLICKHOUSE_DATABASE="${CLICKHOUSE_DATABASE:-default}"
CLICKHOUSE_USER="${CLICKHOUSE_USER:-default}"
CLICKHOUSE_PASSWORD="${CLICKHOUSE_PASSWORD:-}"

curl -sS -X POST "${CONNECT_URL}/connectors" \
  -H 'Content-Type: application/json' \
  -d @- <<JSON
{
  "name": "clickhouse-sink",
  "config": {
    "connector.class": "com.clickhouse.kafka.connect.ClickHouseSinkConnector",
    "tasks.max": "1",
    "topics": "${TOPIC}",
    "clickhouse.server": "${CLICKHOUSE_HOST}",
    "clickhouse.port": "${CLICKHOUSE_PORT}",
    "clickhouse.database": "${CLICKHOUSE_DATABASE}",
    "clickhouse.user": "${CLICKHOUSE_USER}",
    "clickhouse.password": "${CLICKHOUSE_PASSWORD}",
    "clickhouse.table": "logs_raw",
    "value.converter": "org.apache.kafka.connect.json.JsonConverter",
    "value.converter.schemas.enable": "false"
  }
}
JSON
