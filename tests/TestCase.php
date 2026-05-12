<?php

namespace DGarbs51\JobExecutionRecorder\Tests;

use DGarbs51\JobExecutionRecorder\JobExecutionRecorderServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            JobExecutionRecorderServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('database.connections.analytics', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('auth.defaults.guard', 'web');
        $app['config']->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => \App\Models\User::class,
        ]);
        $app['config']->set('session.driver', 'array');
        $app['config']->set('job-execution-recorder.dashboard.enabled', true);
        $app['config']->set('job-execution-recorder.dashboard.path', 'jobs/execution/dashboard');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->createJobExecutionsTable('testing');
        $this->createJobExecutionsTable('analytics');
    }

    private function createJobExecutionsTable(string $connection): void
    {
        $schema = Schema::connection($connection);

        $schema->dropIfExists('job_executions');

        $schema->create('job_executions', function (Blueprint $table): void {
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
}
