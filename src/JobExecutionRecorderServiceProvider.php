<?php

namespace DGarbs51\JobExecutionRecorder;

use DGarbs51\JobExecutionRecorder\Listeners\RecordJobExecution;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class JobExecutionRecorderServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(JobProcessing::class, [RecordJobExecution::class, 'handleProcessing']);
        Event::listen(JobProcessed::class, [RecordJobExecution::class, 'handleProcessed']);
        Event::listen(JobExceptionOccurred::class, [RecordJobExecution::class, 'handleExceptionOccurred']);
        Event::listen(JobFailed::class, [RecordJobExecution::class, 'handleFailed']);

        if ($this->app->runningInConsole()) {
            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ]);
        }
    }
}
