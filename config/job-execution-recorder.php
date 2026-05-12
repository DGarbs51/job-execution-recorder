<?php

return [
    'database_connection' => env('JOB_EXECUTION_DB_CONNECTION', env('DB_CONNECTION')),

    'dashboard' => [
        'enabled' => env('JOB_EXECUTION_RECORDER_DASHBOARD_ENABLED', true),
        'path' => env('JOB_EXECUTION_RECORDER_DASHBOARD_PATH', 'jobs/execution/dashboard'),
        'name' => 'job-execution-recorder.dashboard',
        'view' => 'job-execution-recorder::dashboard',
        'middleware' => ['web', 'auth'],
        'gate' => 'viewJobExecutionDashboard',
        'allowed_emails' => array_values(array_filter(array_map(
            static fn (string $email): string => trim($email),
            explode(',', (string) env('JOB_EXECUTION_RECORDER_DASHBOARD_ALLOWED_EMAILS', ''))
        ))),
    ],
];
