<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('job_executions')) {
            return;
        }

        Schema::create('job_executions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('job_class');
            $table->string('job_class_short');
            $table->string('queue')->nullable();
            $table->string('connection')->nullable();
            $table->string('message_group')->nullable();
            $table->string('status');
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('wait_ms')->nullable();
            $table->unsignedInteger('payload_size')->nullable();
            $table->text('exception_message')->nullable();
            $table->string('exception_class')->nullable();
            $table->timestamps();

            $table->index('started_at');
            $table->index(['job_class_short', 'started_at']);
            $table->index(['queue', 'started_at']);
            $table->index(['status', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_executions');
    }
};
