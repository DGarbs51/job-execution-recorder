<?php

namespace DGarbs51\JobExecutionRecorder\Tests\Feature;

use App\Models\User;
use DGarbs51\JobExecutionRecorder\Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    public function test_dashboard_allows_whitelisted_user_in_non_local_environment(): void
    {
        config()->set('job-execution-recorder.dashboard.allowed_emails', ['allowed@example.com']);

        $this->actingAs(new User(['email' => 'allowed@example.com']));

        $this->get('/jobs/execution/dashboard')
            ->assertOk()
            ->assertSee('Job Execution Dashboard');
    }

    public function test_dashboard_denies_non_whitelisted_user_in_non_local_environment(): void
    {
        config()->set('job-execution-recorder.dashboard.allowed_emails', ['allowed@example.com']);

        $this->actingAs(new User(['email' => 'blocked@example.com']));

        $this->get('/jobs/execution/dashboard')
            ->assertForbidden();
    }

}
