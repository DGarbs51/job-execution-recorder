# Job Execution Recorder

Job Execution Recorder is a small Laravel package that records queue execution lifecycle events (`processing`, `processed`, `failed`) into a `job_executions` table for monitoring.

## What It Includes

- Queue event listener registration via `JobExecutionRecorderServiceProvider`
- Execution listener implementation in `src/Listeners/RecordJobExecution.php`
- Migration for creating `job_executions`

## Installation

```bash
composer require dgarbs51/job-execution-recorder
php artisan vendor:publish --provider="Drew\JobExecutionRecorder\JobExecutionRecorderServiceProvider"
php artisan migrate
```

## Notes

This package currently expects application-level `JobExecution` model and `JobExecutionStatus` enum under the `App\` namespace. If you want this to be framework-agnostic, extract those into the package as a follow-up.
