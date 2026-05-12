<?php

use DGarbs51\JobExecutionRecorder\Http\Controllers\JobExecutionDashboardController;
use Illuminate\Support\Facades\Route;

$path = trim((string) config('job-execution-recorder.dashboard.path', 'jobs/execution/dashboard'), '/');
$name = config('job-execution-recorder.dashboard.name', 'job-execution-recorder.dashboard');
$ability = config('job-execution-recorder.dashboard.gate', 'viewJobExecutionDashboard');

Route::get($path, JobExecutionDashboardController::class)
    ->name((string) $name)
    ->middleware('can:'.(string) $ability);
