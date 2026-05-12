<?php

namespace DGarbs51\JobExecutionRecorder\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class JobExecutionDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $range = (string) $request->query('range', '24h');
        $since = $this->resolveSince($range);
        $jobFilter = $this->nullableString($request->query('job'));
        $messageGroupFilter = $this->nullableString($request->query('message_group'));

        $view = config('job-execution-recorder.dashboard.view', 'job-execution-recorder::dashboard');
        $resolvedView = is_string($view) && $view !== '' ? $view : 'job-execution-recorder::dashboard';

        return view($resolvedView, [
            'range' => $range,
            'jobFilter' => $jobFilter,
            'messageGroupFilter' => $messageGroupFilter,
            'summary' => $this->buildSummary($since, $jobFilter, $messageGroupFilter),
            'queueStats' => $this->buildQueueStats($since, $jobFilter, $messageGroupFilter),
            'jobStats' => $this->buildJobStats($since, $jobFilter, $messageGroupFilter),
            'messageGroupStats' => $this->buildMessageGroupStats($since, $jobFilter, $messageGroupFilter),
            'recentExecutions' => $this->buildRecentExecutions($jobFilter, $messageGroupFilter),
            'jobClasses' => $this->jobClasses(),
            'messageGroups' => $this->messageGroups(),
        ]);
    }

    private function buildSummary(Carbon $since, ?string $jobFilter, ?string $messageGroupFilter): array
    {
        $stats = $this->filteredQuery($since, $jobFilter, $messageGroupFilter)
            ->whereIn('status', ['succeeded', 'failed'])
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'succeeded' THEN 1 ELSE 0 END) as succeeded")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->selectRaw('AVG(duration_ms) as avg_duration_ms')
            ->selectRaw('AVG(wait_ms) as avg_wait_ms')
            ->first();

        $total = (int) ($stats->total ?? 0);
        $succeeded = (int) ($stats->succeeded ?? 0);

        return [
            'total' => $total,
            'succeeded' => $succeeded,
            'failed' => (int) ($stats->failed ?? 0),
            'success_rate' => $total > 0 ? round(($succeeded / $total) * 100, 1) : 0.0,
            'avg_duration_ms' => (int) round((float) ($stats->avg_duration_ms ?? 0)),
            'avg_wait_ms' => (int) round((float) ($stats->avg_wait_ms ?? 0)),
        ];
    }

    private function buildQueueStats(Carbon $since, ?string $jobFilter, ?string $messageGroupFilter): array
    {
        return $this->filteredQuery($since, $jobFilter, $messageGroupFilter)
            ->whereIn('status', ['succeeded', 'failed'])
            ->groupBy('queue')
            ->selectRaw('COALESCE(queue, ?) as queue', ['default'])
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'succeeded' THEN 1 ELSE 0 END) as succeeded")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->selectRaw('ROUND(AVG(duration_ms)) as avg_duration_ms')
            ->selectRaw('ROUND(AVG(wait_ms)) as avg_wait_ms')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'queue' => $row->queue,
                'total' => (int) $row->total,
                'succeeded' => (int) $row->succeeded,
                'failed' => (int) $row->failed,
                'avg_duration_ms' => (int) ($row->avg_duration_ms ?? 0),
                'avg_wait_ms' => (int) ($row->avg_wait_ms ?? 0),
            ])
            ->all();
    }

    private function buildJobStats(Carbon $since, ?string $jobFilter, ?string $messageGroupFilter): array
    {
        return $this->filteredQuery($since, $jobFilter, $messageGroupFilter)
            ->whereIn('status', ['succeeded', 'failed'])
            ->groupBy('job_class_short')
            ->selectRaw('job_class_short as job')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'succeeded' THEN 1 ELSE 0 END) as succeeded")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->selectRaw('ROUND(AVG(duration_ms)) as avg_duration_ms')
            ->selectRaw('ROUND(AVG(wait_ms)) as avg_wait_ms')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'job' => $row->job,
                'total' => (int) $row->total,
                'succeeded' => (int) $row->succeeded,
                'failed' => (int) $row->failed,
                'avg_duration_ms' => (int) ($row->avg_duration_ms ?? 0),
                'avg_wait_ms' => (int) ($row->avg_wait_ms ?? 0),
            ])
            ->all();
    }

    private function buildMessageGroupStats(Carbon $since, ?string $jobFilter, ?string $messageGroupFilter): array
    {
        return $this->filteredQuery($since, $jobFilter, $messageGroupFilter)
            ->whereNotNull('message_group')
            ->where('message_group', '<>', '')
            ->whereIn('status', ['succeeded', 'failed'])
            ->groupBy('message_group')
            ->selectRaw('message_group')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'succeeded' THEN 1 ELSE 0 END) as succeeded")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->selectRaw('ROUND(AVG(duration_ms)) as avg_duration_ms')
            ->selectRaw('ROUND(AVG(wait_ms)) as avg_wait_ms')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'message_group' => $row->message_group,
                'total' => (int) $row->total,
                'succeeded' => (int) $row->succeeded,
                'failed' => (int) $row->failed,
                'avg_duration_ms' => (int) ($row->avg_duration_ms ?? 0),
                'avg_wait_ms' => (int) ($row->avg_wait_ms ?? 0),
            ])
            ->all();
    }

    private function buildRecentExecutions(?string $jobFilter, ?string $messageGroupFilter): array
    {
        return $this->filteredQuery(null, $jobFilter, $messageGroupFilter)
            ->orderByDesc('started_at')
            ->limit(50)
            ->get([
                'id',
                'job_class',
                'job_class_short',
                'queue',
                'connection',
                'message_group',
                'status',
                'duration_ms',
                'wait_ms',
                'started_at',
                'finished_at',
                'payload_size',
                'exception_class',
                'exception_message',
            ])
            ->map(fn ($row): array => [
                'id' => $row->id,
                'job' => $row->job_class_short,
                'job_class' => $row->job_class,
                'queue' => $row->queue ?: 'default',
                'connection' => $row->connection ?: 'default',
                'message_group' => $row->message_group,
                'status' => $row->status,
                'duration_ms' => $row->duration_ms !== null ? (int) $row->duration_ms : null,
                'wait_ms' => $row->wait_ms !== null ? (int) $row->wait_ms : null,
                'started_at' => $this->asIso8601($row->started_at),
                'finished_at' => $this->asIso8601($row->finished_at),
                'payload_size' => $row->payload_size !== null ? (int) $row->payload_size : null,
                'exception_class' => $row->exception_class,
                'exception_message' => $row->exception_message,
            ])
            ->all();
    }

    private function jobClasses(): array
    {
        return $this->table()
            ->distinct()
            ->whereNotNull('job_class_short')
            ->orderBy('job_class_short')
            ->pluck('job_class_short')
            ->all();
    }

    private function messageGroups(): array
    {
        return $this->table()
            ->distinct()
            ->whereNotNull('message_group')
            ->where('message_group', '<>', '')
            ->orderBy('message_group')
            ->pluck('message_group')
            ->all();
    }

    private function filteredQuery(?Carbon $since, ?string $jobFilter, ?string $messageGroupFilter): Builder
    {
        return $this->table()
            ->when($since !== null, fn (Builder $query) => $query->where('started_at', '>=', $since))
            ->when($jobFilter !== null, fn (Builder $query) => $query->where('job_class_short', $jobFilter))
            ->when($messageGroupFilter !== null, fn (Builder $query) => $query->where('message_group', $messageGroupFilter));
    }

    private function table(): Builder
    {
        return DB::connection($this->connectionName())->table('job_executions');
    }

    private function connectionName(): ?string
    {
        $connection = config('job-execution-recorder.database_connection');

        return is_string($connection) && $connection !== '' ? $connection : null;
    }

    private function resolveSince(string $range): Carbon
    {
        return match ($range) {
            '5m' => now()->subMinutes(5),
            '30m' => now()->subMinutes(30),
            '1h' => now()->subHour(),
            '3h' => now()->subHours(3),
            '6h' => now()->subHours(6),
            '12h' => now()->subHours(12),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            default => now()->subDay(),
        };
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function asIso8601(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Carbon::parse($value)->toISOString();
    }
}
