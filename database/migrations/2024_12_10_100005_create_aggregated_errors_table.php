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
        Schema::create('aggregated_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->string('error_hash', 64);
            $table->text('last_message');
            $table->string('level');
            $table->timestamp('last_seen_at');
            $table->bigInteger('count_total')->default(0);
            $table->bigInteger('count_since_last_deploy')->default(0);
            $table->foreignId('last_deployment_id')->nullable()->constrained('deployments')->onDelete('set null');
            $table->jsonb('sample_event')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'error_hash']);
            $table->index('last_seen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aggregated_errors');
    }
};
