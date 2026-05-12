<?php

namespace DGarbs51\JobExecutionRecorder\Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use DGarbs51\JobExecutionRecorder\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardDataTest extends TestCase
{
    public function test_message_groups_section_is_hidden_when_no_message_groups_exist(): void
    {
        config()->set('job-execution-recorder.dashboard.allowed_emails', ['allowed@example.com']);

        $this->insertExecution([
            'job_class' => 'App\\Jobs\\SyncOrders',
            'job_class_short' => 'SyncOrders',
            'queue' => 'orders',
            'message_group' => null,
        ]);

        $this->actingAs(new User(['email' => 'allowed@example.com']));

        $this->get('/jobs/execution/dashboard')
            ->assertOk()
            ->assertDontSee('Message Groups');
    }

    public function test_message_groups_section_is_shown_when_message_groups_exist(): void
    {
        config()->set('job-execution-recorder.dashboard.allowed_emails', ['allowed@example.com']);

        $this->insertExecution([
            'job_class' => 'App\\Jobs\\SyncOrders',
            'job_class_short' => 'SyncOrders',
            'queue' => 'orders',
            'message_group' => 'group-a',
        ]);

        $this->actingAs(new User(['email' => 'allowed@example.com']));

        $this->get('/jobs/execution/dashboard')
            ->assertOk()
            ->assertSee('Message Groups')
            ->assertSee('group-a');
    }

    public function test_dashboard_renders_when_app_uses_carbon_immutable(): void
    {
        Date::use(CarbonImmutable::class);

        try {
            config()->set('job-execution-recorder.dashboard.allowed_emails', ['allowed@example.com']);

            $this->insertExecution([
                'job_class' => 'App\\Jobs\\SyncOrders',
                'job_class_short' => 'SyncOrders',
                'queue' => 'orders',
                'message_group' => null,
            ]);

            $this->actingAs(new User(['email' => 'allowed@example.com']));

            $this->get('/jobs/execution/dashboard')->assertOk();
        } finally {
            Date::useDefault();
        }
    }

    public function test_dashboard_supports_exact_message_group_filtering(): void
    {
        config()->set('job-execution-recorder.dashboard.allowed_emails', ['allowed@example.com']);

        $this->insertExecution([
            'job_class' => 'App\\Jobs\\SyncOrders',
            'job_class_short' => 'SyncOrders',
            'queue' => 'orders-queue',
            'message_group' => 'group-a',
        ]);
        $this->insertExecution([
            'job_class' => 'App\\Jobs\\SyncCatalog',
            'job_class_short' => 'SyncCatalog',
            'queue' => 'catalog-queue',
            'message_group' => 'group-b',
        ]);

        $this->actingAs(new User(['email' => 'allowed@example.com']));

        $this->get('/jobs/execution/dashboard?message_group=group-a')
            ->assertOk()
            ->assertSee('orders-queue')
            ->assertDontSee('catalog-queue');
    }

    private function insertExecution(array $overrides, string $connection = 'testing'): void
    {
        $now = Carbon::now();

        DB::connection($connection)->table('job_executions')->insert(array_merge([
            'id' => (string) Str::ulid(),
            'job_class' => 'App\\Jobs\\DefaultJob',
            'job_class_short' => 'DefaultJob',
            'queue' => 'default',
            'connection' => 'sync',
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
        ], $overrides));
    }
}
