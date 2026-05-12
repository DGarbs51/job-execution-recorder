<?php

namespace DGarbs51\JobExecutionRecorder;

use DGarbs51\JobExecutionRecorder\Listeners\RecordJobExecution;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class JobExecutionRecorderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/job-execution-recorder.php',
            'job-execution-recorder',
        );
    }

    public function boot(): void
    {
        $this->defineDashboardGate();
        $this->registerDashboardRoute();
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'job-execution-recorder');

        Event::listen(JobProcessing::class, [RecordJobExecution::class, 'handleProcessing']);
        Event::listen(JobProcessed::class, [RecordJobExecution::class, 'handleProcessed']);
        Event::listen(JobExceptionOccurred::class, [RecordJobExecution::class, 'handleExceptionOccurred']);
        Event::listen(JobFailed::class, [RecordJobExecution::class, 'handleFailed']);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/job-execution-recorder.php' => config_path('job-execution-recorder.php'),
            ], 'job-execution-recorder-config');

            if (method_exists($this, 'publishesMigrations')) {
                $this->publishesMigrations([
                    __DIR__.'/../database/migrations' => database_path('migrations'),
                ]);
            } else {
                $this->publishes([
                    __DIR__.'/../database/migrations' => database_path('migrations'),
                ], 'job-execution-recorder-migrations');
            }

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/job-execution-recorder'),
            ], 'job-execution-recorder-views');
        }
    }

    private function defineDashboardGate(): void
    {
        $ability = config('job-execution-recorder.dashboard.gate', 'viewJobExecutionDashboard');

        if (! is_string($ability) || $ability === '' || Gate::has($ability)) {
            return;
        }

        Gate::define($ability, function ($user = null): bool {
            if ($this->app->environment('local')) {
                return true;
            }

            if (! $user || ! isset($user->email)) {
                return false;
            }

            $allowedEmails = config('job-execution-recorder.dashboard.allowed_emails', []);
            if (! is_array($allowedEmails)) {
                return false;
            }

            return in_array($user->email, $allowedEmails, true);
        });
    }

    private function registerDashboardRoute(): void
    {
        if (! config('job-execution-recorder.dashboard.enabled', true)) {
            return;
        }

        $middleware = config('job-execution-recorder.dashboard.middleware', ['web', 'auth']);
        $routeMiddleware = is_array($middleware) ? $middleware : ['web', 'auth'];

        Route::middleware($routeMiddleware)->group(__DIR__.'/../routes/web.php');
    }
}
