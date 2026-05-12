<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = $this->schema();

        if ($schema->hasTable('job_executions')) {
            return;
        }

        $schema->create('job_executions', function (Blueprint $table) {
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
            $table->index(['message_group', 'started_at']);
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('job_executions');
    }

    private function schema()
    {
        $connection = env('JOB_EXECUTION_DB_CONNECTION', env('DB_CONNECTION'));
        $resolvedConnection = is_string($connection) && $connection !== '' ? $connection : null;

        return Schema::connection($resolvedConnection ?? config('database.default'));
    }
};
