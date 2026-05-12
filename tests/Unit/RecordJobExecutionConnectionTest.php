<?php

namespace DGarbs51\JobExecutionRecorder\Tests\Unit;

use DGarbs51\JobExecutionRecorder\Listeners\RecordJobExecution;
use DGarbs51\JobExecutionRecorder\Tests\TestCase;
use ReflectionMethod;

class RecordJobExecutionConnectionTest extends TestCase
{
    public function test_listener_query_uses_configured_connection(): void
    {
        config()->set('job-execution-recorder.database_connection', 'analytics');

        $listener = new RecordJobExecution;
        $method = new ReflectionMethod($listener, 'jobExecutionQuery');
        $method->setAccessible(true);

        $query = $method->invoke($listener);

        $this->assertSame('analytics', $query->getConnection()->getName());
    }

    public function test_listener_query_uses_default_connection_when_config_is_empty(): void
    {
        config()->set('job-execution-recorder.database_connection', null);

        $listener = new RecordJobExecution;
        $method = new ReflectionMethod($listener, 'jobExecutionQuery');
        $method->setAccessible(true);

        $query = $method->invoke($listener);

        $this->assertSame('testing', $query->getConnection()->getName());
    }
}
