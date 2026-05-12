# Job Execution Recorder

Job Execution Recorder is a Laravel package that records queue execution lifecycle events (`processing`, `processed`, `failed`) into a `job_executions` table and provides a configurable dashboard.

## Supported Versions

- Laravel `10.x`, `11.x`, `12.x`, and `13.x`
- PHP `8.2+`

## What It Includes

- Queue event listener registration via `JobExecutionRecorderServiceProvider`
- Execution listener implementation in `src/Listeners/RecordJobExecution.php`
- Migration for creating `job_executions`
- Configurable dashboard route + Blade view
- Configurable database connection for writes and dashboard reads

## Installation

```bash
composer require dgarbs51/job-execution-recorder
php artisan vendor:publish --provider="DGarbs51\JobExecutionRecorder\JobExecutionRecorderServiceProvider"
php artisan migrate
```

## Publish Options

```bash
# Config
php artisan vendor:publish --tag=job-execution-recorder-config

# Views
php artisan vendor:publish --tag=job-execution-recorder-views
```

## Configuration

Publish config to create `config/job-execution-recorder.php` and then tune values:

```php
return [
    'database_connection' => env('JOB_EXECUTION_DB_CONNECTION', env('DB_CONNECTION')),
    'dashboard' => [
        'enabled' => env('JOB_EXECUTION_RECORDER_DASHBOARD_ENABLED', true),
        'path' => env('JOB_EXECUTION_RECORDER_DASHBOARD_PATH', 'jobs/execution/dashboard'),
        'name' => 'job-execution-recorder.dashboard',
        'view' => 'job-execution-recorder::dashboard',
        'middleware' => ['web', 'auth'],
        'gate' => 'viewJobExecutionDashboard',
        'allowed_emails' => [...],
    ],
];
```

### Environment Variables

- `JOB_EXECUTION_DB_CONNECTION`: DB connection for recorder writes + dashboard reads. Defaults to `DB_CONNECTION`.
- `JOB_EXECUTION_RECORDER_DASHBOARD_ENABLED`: enable/disable dashboard route registration.
- `JOB_EXECUTION_RECORDER_DASHBOARD_PATH`: route path (default `/jobs/execution/dashboard`).
- `JOB_EXECUTION_RECORDER_DASHBOARD_ALLOWED_EMAILS`: comma-separated email allowlist for non-local environments.
- `dashboard.view` can be changed in config to render a custom published view path.

## Dashboard Access (Horizon-style)

- Route middleware defaults to `web` + `auth`.
- Route is additionally protected by the `can:viewJobExecutionDashboard` ability (configurable with `dashboard.gate`).
- In local environments, access is allowed by default.
- In non-local environments, access is allowed only when the authenticated user's email is in `dashboard.allowed_emails` (or via your custom gate definition).

You can override the gate in your app:

```php
Gate::define('viewJobExecutionDashboard', function ($user) {
    return $user->isAdmin();
});
```

## Dashboard Filters

The dashboard supports:
- Time range filtering
- Exact `job_class_short` filtering
- Exact `message_group` filtering

Message group metrics are displayed only when at least one message-group row exists for the current filter context.

## Migration / Index Notes

The package migration is designed to run on PostgreSQL, MySQL, and SQLite. It includes indexes aligned with dashboard access patterns:

- `started_at`
- `job_class_short, started_at`
- `queue, started_at`
- `status, started_at`
- `message_group, started_at`

## Notes

This package currently expects application-level `JobExecution` model and `JobExecutionStatus` enum under the `App\` namespace. If you want this to be framework-agnostic, extract those into the package as a follow-up.
