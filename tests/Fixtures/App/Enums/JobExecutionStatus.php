<?php

namespace App\Enums;

enum JobExecutionStatus: string
{
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
