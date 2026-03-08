FROM confluentinc/cp-kafka-connect:7.6.0

ARG CLICKHOUSE_CONNECT_VERSION=1.3.4
ARG CLICKHOUSE_CONNECT_ZIP=clickhouse-kafka-connect-1.3.4.zip

RUN mkdir -p /usr/share/java/clickhouse-kafka-connect \
    && curl -sSL -o /tmp/clickhouse-kafka-connect.zip \
       https://github.com/ClickHouse/clickhouse-kafka-connect/releases/download/v${CLICKHOUSE_CONNECT_VERSION}/${CLICKHOUSE_CONNECT_ZIP} \
    && unzip /tmp/clickhouse-kafka-connect.zip -d /usr/share/java/clickhouse-kafka-connect \
    && rm /tmp/clickhouse-kafka-connect.zip
