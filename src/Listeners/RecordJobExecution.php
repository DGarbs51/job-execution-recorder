<?php

namespace DGarbs51\JobExecutionRecorder\Listeners;

use App\Enums\JobExecutionStatus;
use App\Models\JobExecution;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Carbon;
use Throwable;

class RecordJobExecution
{
    /** @var array<string, string> Maps job UUID to execution ULID */
    protected static array $executionMap = [];

    public static function flush(): void
    {
        static::$executionMap = [];
    }

    public function handleProcessing(JobProcessing $event): void
    {
        try {
            $uuid = $event->job->uuid();
            if (! $uuid) {
                return;
            }

            if (isset(static::$executionMap[$uuid])) {
                return;
            }

            $jobClass = $this->resolveJobClass($event->job);
            if (! $jobClass) {
                return;
            }

            $now = now();
            $payload = $event->job->payload();
            $createdAt = $payload['createdAt'] ?? $payload['pushedAt'] ?? null;
            $queuedAt = $createdAt ? Carbon::createFromTimestamp($createdAt) : null;
            $waitMs = $queuedAt ? (int) round($queuedAt->diffInMilliseconds($now)) : null;

            $execution = $this->jobExecutionQuery()->create([
                'job_class' => $jobClass,
                'job_class_short' => class_basename($jobClass),
                'queue' => $event->job->getQueue(),
                'connection' => $event->connectionName,
                'message_group' => $this->resolveMessageGroup($event->job),
                'status' => JobExecutionStatus::Processing,
                'queued_at' => $queuedAt,
                'started_at' => $now,
                'wait_ms' => $waitMs,
                'payload_size' => strlen($event->job->getRawBody()),
            ]);

            static::$executionMap[$uuid] = $execution->id;
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function handleProcessed(JobProcessed $event): void
    {
        try {
            $this->finishExecution($event->job->uuid(), JobExecutionStatus::Succeeded);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function handleExceptionOccurred(JobExceptionOccurred $event): void
    {
        try {
            $this->finishExecution(
                $event->job->uuid(),
                JobExecutionStatus::Failed,
                $event->exception,
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function handleFailed(JobFailed $event): void
    {
        try {
            $this->finishExecution(
                $event->job->uuid(),
                JobExecutionStatus::Failed,
                $event->exception,
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function finishExecution(?string $uuid, JobExecutionStatus $status, ?Throwable $exception = null): void
    {
        if (! $uuid || ! isset(static::$executionMap[$uuid])) {
            return;
        }

        $executionId = static::$executionMap[$uuid];
        unset(static::$executionMap[$uuid]);

        $execution = $this->jobExecutionQuery()->find($executionId);
        if (! $execution) {
            return;
        }

        $now = now();
        $durationMs = (int) round($execution->started_at->diffInMilliseconds($now));

        $execution->update([
            'status' => $status,
            'finished_at' => $now,
            'duration_ms' => $durationMs,
            'exception_message' => $exception?->getMessage(),
            'exception_class' => $exception ? get_class($exception) : null,
        ]);
    }

    private function resolveJobClass(mixed $job): ?string
    {
        $payload = $job->payload();

        return $payload['displayName'] ?? null;
    }

    private function resolveMessageGroup(mixed $job): ?string
    {
        $payload = $job->payload();
        $payloadGroup = $payload['messageGroup'] ?? $payload['message_group'] ?? null;

        if (is_string($payloadGroup) && $payloadGroup !== '') {
            return $payloadGroup;
        }

        $command = $payload['data']['command'] ?? null;
        if (! is_string($command) || $command === '') {
            return null;
        }

        set_error_handler(static fn (): bool => true);

        try {
            $decoded = unserialize($command);
        } finally {
            restore_error_handler();
        }
        if (! is_object($decoded)) {
            return null;
        }

        $group = $decoded->messageGroup ?? (method_exists($decoded, 'messageGroup') ? $decoded->messageGroup() : null);

        return is_string($group) && $group !== '' ? $group : null;
    }

    private function jobExecutionQuery()
    {
        $connection = config('job-execution-recorder.database_connection');
        $resolvedConnection = is_string($connection) && $connection !== '' ? $connection : null;

        return $resolvedConnection ? JobExecution::on($resolvedConnection) : JobExecution::query();
    }
}
