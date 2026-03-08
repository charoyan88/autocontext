<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ClickhouseClient
{
    public function execute(string $sql): void
    {
        $this->request($sql);
    }

    public function insertJsonEachRow(string $table, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $payload = '';
        foreach ($rows as $row) {
            $payload .= json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
        }

        $query = "INSERT INTO {$table} FORMAT JSONEachRow";
        $this->request($query, $payload);
    }

    private function request(string $query, ?string $body = null): void
    {
        $url = sprintf('http://%s:%s/', config('clickhouse.host'), config('clickhouse.port'));

        $params = [
            'query' => $query,
            'database' => config('clickhouse.database'),
            'user' => config('clickhouse.user'),
        ];

        $password = (string) config('clickhouse.password');
        if ($password !== '') {
            $params['password'] = $password;
        }

        $response = Http::withHeaders([
            'Content-Type' => 'text/plain',
        ])->send('POST', $url, [
            'query' => $params,
            'body' => $body,
        ]);

        $response->throw();
    }
}
