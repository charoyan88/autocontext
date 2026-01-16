<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_hourly_stats', function (Blueprint $table) {
            $table->bigInteger('forward_failed_count')->default(0)->after('deployment_error_count');
            $table->index('forward_failed_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_hourly_stats', function (Blueprint $table) {
            $table->dropIndex(['forward_failed_count']);
            $table->dropColumn('forward_failed_count');
        });
    }
};
