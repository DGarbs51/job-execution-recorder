<?php

namespace DGarbs51\JobExecutionRecorder\Tests\Feature;

use App\Models\User;
use DGarbs51\JobExecutionRecorder\Tests\TestCase;

class DashboardCustomPathTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('job-execution-recorder.dashboard.path', 'custom/jobs/executions');
    }

    public function test_dashboard_route_uses_configured_path(): void
    {
        config()->set('job-execution-recorder.dashboard.allowed_emails', ['allowed@example.com']);

        $this->actingAs(new User(['email' => 'allowed@example.com']));

        $this->get('/custom/jobs/executions')
            ->assertOk()
            ->assertSee('Job Execution Dashboard');
    }
}
