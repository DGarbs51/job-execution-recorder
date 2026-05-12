<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Job Execution Dashboard</title>
    <style>
        :root { color-scheme: light dark; }
        body { font-family: Inter, system-ui, -apple-system, sans-serif; margin: 0; padding: 24px; }
        h1, h2 { margin: 0 0 12px; }
        .muted { opacity: .75; font-size: 14px; }
        .controls { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin: 20px 0; }
        .field { display: flex; flex-direction: column; gap: 6px; }
        .field label { font-size: 13px; opacity: .85; }
        .field select, .field button { padding: 8px 10px; font-size: 14px; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin: 14px 0 24px; }
        .card { border: 1px solid rgba(128, 128, 128, .35); border-radius: 10px; padding: 12px; }
        .card .label { font-size: 12px; opacity: .75; margin-bottom: 6px; }
        .card .value { font-size: 22px; font-weight: 600; }
        .section { margin-top: 26px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid rgba(128, 128, 128, .25); font-size: 14px; vertical-align: top; }
        th { font-size: 12px; text-transform: uppercase; letter-spacing: .02em; opacity: .8; }
        .empty { padding: 14px 0; opacity: .75; }
        .status { display: inline-block; border-radius: 999px; padding: 2px 8px; font-size: 12px; border: 1px solid rgba(128, 128, 128, .4); }
        .status.succeeded { color: #16a34a; }
        .status.failed { color: #dc2626; }
    </style>
</head>
<body>
<h1>Job Execution Dashboard</h1>
<p class="muted">Configurable queue execution metrics with job and message-group filters.</p>

<form method="GET" class="controls">
    <div class="field">
        <label for="range">Range</label>
        <select id="range" name="range">
            @php($rangeOptions = ['5m' => '5 minutes', '30m' => '30 minutes', '1h' => '1 hour', '3h' => '3 hours', '6h' => '6 hours', '12h' => '12 hours', '24h' => '24 hours', '7d' => '7 days', '30d' => '30 days'])
            @foreach($rangeOptions as $value => $label)
                <option value="{{ $value }}" @selected($range === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="job">Job Class</label>
        <select id="job" name="job">
            <option value="">All jobs</option>
            @foreach($jobClasses as $jobClass)
                <option value="{{ $jobClass }}" @selected($jobFilter === $jobClass)>{{ $jobClass }}</option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="message_group">Message Group</label>
        <select id="message_group" name="message_group">
            <option value="">All message groups</option>
            @foreach($messageGroups as $group)
                <option value="{{ $group }}" @selected($messageGroupFilter === $group)>{{ $group }}</option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label>&nbsp;</label>
        <button type="submit">Apply Filters</button>
    </div>
</form>

<section class="cards">
    <article class="card">
        <div class="label">Total Executions</div>
        <div class="value">{{ number_format($summary['total']) }}</div>
    </article>
    <article class="card">
        <div class="label">Succeeded</div>
        <div class="value">{{ number_format($summary['succeeded']) }}</div>
    </article>
    <article class="card">
        <div class="label">Failed</div>
        <div class="value">{{ number_format($summary['failed']) }}</div>
    </article>
    <article class="card">
        <div class="label">Success Rate</div>
        <div class="value">{{ number_format($summary['success_rate'], 1) }}%</div>
    </article>
    <article class="card">
        <div class="label">Avg Duration</div>
        <div class="value">{{ number_format($summary['avg_duration_ms']) }}ms</div>
    </article>
    <article class="card">
        <div class="label">Avg Wait</div>
        <div class="value">{{ number_format($summary['avg_wait_ms']) }}ms</div>
    </article>
</section>

<section class="section">
    <h2>Queues</h2>
    @if(count($queueStats) === 0)
        <p class="empty">No queue data in this range.</p>
    @else
        <table>
            <thead>
            <tr>
                <th>Queue</th>
                <th>Total</th>
                <th>Succeeded</th>
                <th>Failed</th>
                <th>Avg Duration</th>
                <th>Avg Wait</th>
            </tr>
            </thead>
            <tbody>
            @foreach($queueStats as $row)
                <tr>
                    <td>{{ $row['queue'] }}</td>
                    <td>{{ number_format($row['total']) }}</td>
                    <td>{{ number_format($row['succeeded']) }}</td>
                    <td>{{ number_format($row['failed']) }}</td>
                    <td>{{ number_format($row['avg_duration_ms']) }}ms</td>
                    <td>{{ number_format($row['avg_wait_ms']) }}ms</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</section>

<section class="section">
    <h2>Jobs</h2>
    @if(count($jobStats) === 0)
        <p class="empty">No job data in this range.</p>
    @else
        <table>
            <thead>
            <tr>
                <th>Job</th>
                <th>Total</th>
                <th>Succeeded</th>
                <th>Failed</th>
                <th>Avg Duration</th>
                <th>Avg Wait</th>
            </tr>
            </thead>
            <tbody>
            @foreach($jobStats as $row)
                <tr>
                    <td>{{ $row['job'] }}</td>
                    <td>{{ number_format($row['total']) }}</td>
                    <td>{{ number_format($row['succeeded']) }}</td>
                    <td>{{ number_format($row['failed']) }}</td>
                    <td>{{ number_format($row['avg_duration_ms']) }}ms</td>
                    <td>{{ number_format($row['avg_wait_ms']) }}ms</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</section>

@if(count($messageGroupStats) > 0)
    <section class="section">
        <h2>Message Groups</h2>
        <table>
            <thead>
            <tr>
                <th>Message Group</th>
                <th>Total</th>
                <th>Succeeded</th>
                <th>Failed</th>
                <th>Avg Duration</th>
                <th>Avg Wait</th>
            </tr>
            </thead>
            <tbody>
            @foreach($messageGroupStats as $row)
                <tr>
                    <td>{{ $row['message_group'] }}</td>
                    <td>{{ number_format($row['total']) }}</td>
                    <td>{{ number_format($row['succeeded']) }}</td>
                    <td>{{ number_format($row['failed']) }}</td>
                    <td>{{ number_format($row['avg_duration_ms']) }}ms</td>
                    <td>{{ number_format($row['avg_wait_ms']) }}ms</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>
@endif

<section class="section">
    <h2>Recent Executions</h2>
    @if(count($recentExecutions) === 0)
        <p class="empty">No executions yet.</p>
    @else
        <table>
            <thead>
            <tr>
                <th>Started</th>
                <th>Job</th>
                <th>Queue</th>
                <th>Message Group</th>
                <th>Status</th>
                <th>Duration</th>
                <th>Wait</th>
            </tr>
            </thead>
            <tbody>
            @foreach($recentExecutions as $execution)
                <tr>
                    <td>{{ $execution['started_at'] }}</td>
                    <td>
                        <strong>{{ $execution['job'] }}</strong><br>
                        <span class="muted">{{ $execution['job_class'] }}</span>
                    </td>
                    <td>{{ $execution['queue'] }}</td>
                    <td>{{ $execution['message_group'] ?: '—' }}</td>
                    <td><span class="status {{ $execution['status'] }}">{{ $execution['status'] }}</span></td>
                    <td>{{ $execution['duration_ms'] !== null ? number_format($execution['duration_ms']).'ms' : '—' }}</td>
                    <td>{{ $execution['wait_ms'] !== null ? number_format($execution['wait_ms']).'ms' : '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</section>
</body>
</html>
