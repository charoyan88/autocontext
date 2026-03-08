CREATE TABLE IF NOT EXISTS logs_raw (
  project_id UInt64,
  ts DateTime,
  level LowCardinality(String),
  message String,
  service LowCardinality(String),
  region LowCardinality(String),
  path String,
  deployment_version String,
  deployment_environment String,
  deployment_related UInt8,
  is_filtered UInt8,
  is_error UInt8,
  forward_failed UInt8
) ENGINE = MergeTree
PARTITION BY toDate(ts)
ORDER BY (project_id, ts);

CREATE TABLE IF NOT EXISTS logs_hourly (
  project_id UInt64,
  hour DateTime,
  incoming UInt64,
  outgoing UInt64,
  filtered UInt64,
  deployment_errors UInt64,
  forward_failed UInt64
) ENGINE = SummingMergeTree
PARTITION BY toDate(hour)
ORDER BY (project_id, hour);

CREATE MATERIALIZED VIEW IF NOT EXISTS logs_hourly_mv TO logs_hourly AS
SELECT
  project_id,
  toStartOfHour(ts) AS hour,
  count() AS incoming,
  sum(1 - is_filtered) AS outgoing,
  sum(is_filtered) AS filtered,
  sum(is_error * deployment_related) AS deployment_errors,
  sum(forward_failed) AS forward_failed
FROM logs_raw
GROUP BY project_id, hour;
