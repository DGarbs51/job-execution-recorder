<?php

namespace DGarbs51\JobExecutionRecorder\Tests\Feature;

use App\Models\User;
use DGarbs51\JobExecutionRecorder\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseConnectionTest extends TestCase
{
    public function test_dashboard_reads_from_configured_database_connection(): void
    {
        config()->set('job-execution-recorder.database_connection', 'analytics');
        config()->set('job-execution-recorder.dashboard.allowed_emails', ['allowed@example.com']);

        $this->insertExecution('testing', 'DefaultConnectionJob', 'default-queue');
        $this->insertExecution('analytics', 'AnalyticsConnectionJob', 'analytics-queue');

        $this->actingAs(new User(['email' => 'allowed@example.com']));

        $this->get('/jobs/execution/dashboard')
            ->assertOk()
            ->assertSee('AnalyticsConnectionJob')
            ->assertSee('analytics-queue')
            ->assertDontSee('DefaultConnectionJob')
            ->assertDontSee('default-queue');
    }

    private function insertExecution(string $connection, string $jobShort, string $queue): void
    {
        $now = Carbon::now();

        DB::connection($connection)->table('job_executions')->insert([
            'id' => (string) Str::ulid(),
            'job_class' => 'App\\Jobs\\'.$jobShort,
            'job_class_short' => $jobShort,
            'queue' => $queue,
            'connection' => $connection,
            'message_group' => null,
            'status' => 'succeeded',
            'queued_at' => $now->copy()->subSecond(),
            'started_at' => $now->copy()->subSecond(),
            'finished_at' => $now,
            'duration_ms' => 100,
            'wait_ms' => 50,
            'payload_size' => 200,
            'exception_message' => null,
            'exception_class' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
