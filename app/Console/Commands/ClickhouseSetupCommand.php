<?php

namespace App\Console\Commands;

use App\Services\ClickhouseClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClickhouseSetupCommand extends Command
{
    protected $signature = 'clickhouse:setup {--file=database/clickhouse/schema.sql}';
    protected $description = 'Create ClickHouse tables and materialized views';

    public function handle(ClickhouseClient $client): int
    {
        $path = base_path($this->option('file'));
        if (!File::exists($path)) {
            $this->error("Schema file not found: {$path}");
            return self::FAILURE;
        }

        $sql = File::get($path);
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            $client->execute($statement);
        }

        $this->info('ClickHouse schema applied.');
        return self::SUCCESS;
    }
}
