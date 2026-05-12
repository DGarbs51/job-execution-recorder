<?php

namespace App\Models;

use App\Enums\JobExecutionStatus;
use Illuminate\Database\Eloquent\Model;

class JobExecution extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'job_executions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => JobExecutionStatus::class,
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
