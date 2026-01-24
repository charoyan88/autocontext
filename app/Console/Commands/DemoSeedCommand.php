<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DemoSeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the application with demo data for Auto-Context';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting demo seed...');

        Artisan::call('db:seed', [
            '--class' => 'DemoSeeder',
            '--force' => true,
        ]);

        $this->info(Artisan::output());
        $this->info('Demo data seeded successfully!');

        return self::SUCCESS;
    }
}
