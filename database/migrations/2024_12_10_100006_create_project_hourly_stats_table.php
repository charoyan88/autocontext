<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_hourly_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->timestamp('hour_ts');
            $table->bigInteger('incoming_count')->default(0);
            $table->bigInteger('outgoing_count')->default(0);
            $table->bigInteger('filtered_count')->default(0);
            $table->bigInteger('deployment_error_count')->default(0);
            $table->bigInteger('forward_failed_count')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'hour_ts']);
            $table->index('hour_ts');
            $table->index('forward_failed_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_hourly_stats');
    }
};
