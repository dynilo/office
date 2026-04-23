@extends('admin.layout')

@php
    $formatNumber = static fn (int|float $value): string => number_format($value, is_float($value) && floor($value) !== $value ? 1 : 0);
    $formatCost = static fn (int $micros, string $currency): string => $currency.' '.number_format($micros / 1_000_000, 4);
    $barPercent = static fn (int $value, int $total): string => $total === 0 ? '0%' : min(100, max(0, round(($value / $total) * 100, 1))).'%';
@endphp

@section('pageStyles')
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .metric-card,
        .dashboard-panel {
            border: 1px solid rgba(20, 33, 61, 0.08);
            background: rgba(255, 253, 247, 0.82);
            box-shadow: 0 16px 40px rgba(20, 33, 61, 0.06);
        }

        .metric-card {
            display: grid;
            gap: 0.75rem;
            min-height: 11rem;
            padding: 1.2rem;
            border-radius: 1.25rem;
        }

        .metric-card span,
        .panel-kicker {
            color: var(--ink-soft);
            font-size: 0.76rem;
            letter-spacing: 0.13em;
            text-transform: uppercase;
        }

        .metric-card strong {
            display: block;
            font-size: clamp(2rem, 5vw, 3.2rem);
            line-height: 0.9;
        }

        .metric-card p {
            margin: 0;
            color: var(--ink-soft);
            line-height: 1.45;
        }

        .metric-card.is-hot {
            color: #fff9ef;
            background:
                linear-gradient(150deg, rgba(20, 33, 61, 0.94), rgba(196, 106, 45, 0.84)),
                var(--ink);
        }

        .metric-card.is-hot span,
        .metric-card.is-hot p {
            color: rgba(255, 249, 239, 0.78);
        }

        .dashboard-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(24rem, 0.74fr);
            gap: 1rem;
            margin-top: 1rem;
        }

        .dashboard-panel {
            padding: 1.25rem;
            border-radius: 1.25rem;
        }

        .dashboard-panel h3 {
            margin: 0.25rem 0 1rem;
            font-size: 1.18rem;
        }

        .status-bars,
        .activity-list {
            display: grid;
            gap: 0.85rem;
        }

        .bar-row {
            display: grid;
            gap: 0.4rem;
        }

        .bar-label {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            color: var(--ink-soft);
            font-size: 0.9rem;
        }

        .bar-track {
            overflow: hidden;
            height: 0.65rem;
            border-radius: 999px;
            background: rgba(20, 33, 61, 0.08);
        }

        .bar-fill {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--accent), #2f6b4f);
        }

        .activity-table {
            width: 100%;
            border-collapse: collapse;
        }

        .activity-table th,
        .activity-table td {
            padding: 0.78rem 0.65rem;
            border-bottom: 1px solid rgba(20, 33, 61, 0.08);
            text-align: left;
            vertical-align: top;
        }

        .activity-table th {
            color: var(--ink-soft);
            font-size: 0.76rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .activity-table td {
            overflow-wrap: anywhere;
        }

        .pill {
            display: inline-flex;
            padding: 0.28rem 0.58rem;
            border-radius: 999px;
            color: var(--ink-soft);
            background: rgba(20, 33, 61, 0.06);
            font-size: 0.78rem;
            font-weight: 700;
        }

        .pill.good {
            color: var(--ok);
            background: rgba(47, 107, 79, 0.12);
        }

        .pill.bad {
            color: #8b2d22;
            background: rgba(139, 45, 34, 0.12);
        }

        .dashboard-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.65rem;
            padding: 0.65rem 1rem;
            border: 1px solid rgba(196, 106, 45, 0.36);
            border-radius: 999px;
            color: var(--ink);
            background: rgba(255, 255, 255, 0.72);
            font: inherit;
            font-weight: 700;
            text-decoration: none;
        }

        .empty-state {
            margin: 0;
            color: var(--ink-soft);
            line-height: 1.55;
        }

        .attention-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .attention-card {
            display: grid;
            gap: 0.65rem;
            padding: 1rem 1.1rem;
            border: 1px solid rgba(20, 33, 61, 0.08);
            border-radius: 1.15rem;
            background: rgba(255, 253, 247, 0.78);
            box-shadow: 0 14px 34px rgba(20, 33, 61, 0.05);
        }

        .attention-card strong {
            font-size: 1.85rem;
            line-height: 1;
        }

        .attention-card p {
            margin: 0;
            color: var(--ink-soft);
            line-height: 1.45;
        }

        .attention-card a {
            color: var(--accent);
            font-weight: 700;
            text-decoration: none;
        }

        @media (max-width: 1150px) {
            .dashboard-grid,
            .dashboard-layout,
            .attention-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .dashboard-grid,
            .dashboard-layout,
            .attention-grid {
                grid-template-columns: 1fr;
            }

            .activity-table,
            .activity-table tbody,
            .activity-table tr,
            .activity-table td {
                display: block;
            }

            .activity-table thead {
                display: none;
            }
        }
    </style>
@endsection

@section('content')
    <section class="hero">
        <span class="status">Dashboard metrics active</span>
        <h2>Runtime command board.</h2>
        <p>
            A compact operational view over the existing admin APIs: live entity counts, task and execution health,
            usage-cost totals, and recent runtime activity for quick triage.
        </p>
        <div class="dashboard-actions">
            <a class="button" href="{{ route('admin.tasks') }}">Review task queue</a>
            <a class="button" href="{{ route('admin.executions') }}">Open execution monitor</a>
        </div>
    </section>

    <section class="dashboard-grid" aria-label="Runtime KPI cards">
        <article class="metric-card is-hot">
            <span>Active agents</span>
            <strong id="metric-active-agents">{{ $formatNumber($summary['agents']['active']) }}</strong>
            <p>{{ $formatNumber($summary['agents']['total']) }} total registered agents.</p>
        </article>

        <article class="metric-card">
            <span>Queued tasks</span>
            <strong id="metric-queued-tasks">{{ $formatNumber($summary['tasks']['queued']) }}</strong>
            <p>{{ $formatNumber($summary['tasks']['in_progress']) }} in progress, {{ $formatNumber($summary['tasks']['completion_rate']) }}% completion rate.</p>
        </article>

        <article class="metric-card">
            <span>Execution success</span>
            <strong id="metric-execution-success">{{ $formatNumber($summary['executions']['success_rate']) }}%</strong>
            <p>{{ $formatNumber($summary['executions']['running']) }} running, {{ $formatNumber($summary['executions']['failed']) }} failed.</p>
        </article>

        <article class="metric-card">
            <span>Estimated provider cost</span>
            <strong id="metric-provider-cost">{{ $formatCost($summary['costs']['estimated_cost_micros'], $summary['costs']['currency']) }}</strong>
            <p>{{ $formatNumber($summary['costs']['total_tokens']) }} tracked provider tokens.</p>
        </article>
    </section>

    <section class="dashboard-layout" aria-label="Operational attention and runtime recency">
        <div class="dashboard-panel">
            <span class="panel-kicker">Attention queue</span>
            <h3>Operator attention</h3>
            <div class="attention-grid">
                <article class="attention-card">
                    <span class="panel-kicker">Failed executions</span>
                    <strong>{{ $formatNumber($summary['attention']['failed_executions']) }}</strong>
                    <p>Execution failures that need triage or retry review.</p>
                    <a href="{{ route('admin.executions') }}">Open execution monitor</a>
                </article>

                <article class="attention-card">
                    <span class="panel-kicker">Dead letters</span>
                    <strong>{{ $formatNumber($summary['attention']['dead_letters']) }}</strong>
                    <p>Terminal runtime records that escaped normal recovery.</p>
                    <a href="{{ route('admin.audit') }}">Review audit and failures</a>
                </article>

                <article class="attention-card">
                    <span class="panel-kicker">Pending approvals</span>
                    <strong>{{ $formatNumber($summary['attention']['pending_approvals']) }}</strong>
                    <p>Approval-gated runtime work that is waiting on a decision.</p>
                    <a href="{{ route('admin.executions') }}">Inspect blocked executions</a>
                </article>

                <article class="attention-card">
                    <span class="panel-kicker">Unassigned queued tasks</span>
                    <strong>{{ $formatNumber($summary['attention']['unassigned_queued_tasks']) }}</strong>
                    <p>Queued intake that is still waiting for an agent assignment.</p>
                    <a href="{{ route('admin.tasks') }}">Review task queue</a>
                </article>
            </div>
        </div>

        <div class="dashboard-panel">
            <span class="panel-kicker">Runtime recency</span>
            <h3>Last known activity</h3>
            <div class="status-bars">
                <div class="bar-row">
                    <div class="bar-label">
                        <span>Latest task intake</span>
                        <strong>{{ $summary['operations']['latest_task_at'] ?: 'None yet' }}</strong>
                    </div>
                </div>
                <div class="bar-row">
                    <div class="bar-label">
                        <span>Latest execution event</span>
                        <strong>{{ $summary['operations']['latest_execution_at'] ?: 'None yet' }}</strong>
                    </div>
                </div>
                <div class="bar-row">
                    <div class="bar-label">
                        <span>Latest audit event</span>
                        <strong>{{ $summary['operations']['latest_audit_event_at'] ?: 'None yet' }}</strong>
                    </div>
                </div>
                <div class="bar-row">
                    <div class="bar-label">
                        <span>Open runtime issues</span>
                        <strong>{{ $formatNumber($summary['attention']['open_issues_total']) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="dashboard-layout" aria-label="Runtime distributions and recent activity">
        <div class="dashboard-panel">
            <span class="panel-kicker">Task distribution</span>
            <h3>Status mix</h3>
            <div class="status-bars">
                @foreach (['draft', 'queued', 'in_progress', 'completed', 'failed'] as $state)
                    <div class="bar-row">
                        <div class="bar-label">
                            <span>{{ str_replace('_', ' ', $state) }}</span>
                            <strong>{{ $formatNumber($summary['tasks'][$state]) }}</strong>
                        </div>
                        <div class="bar-track">
                            <span class="bar-fill" style="width: {{ $barPercent($summary['tasks'][$state], $summary['tasks']['total']) }}"></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="dashboard-panel">
            <span class="panel-kicker">Execution health</span>
            <h3>Pipeline mix</h3>
            <div class="status-bars">
                @foreach (['pending', 'running', 'succeeded', 'failed'] as $state)
                    <div class="bar-row">
                        <div class="bar-label">
                            <span>{{ $state }}</span>
                            <strong>{{ $formatNumber($summary['executions'][$state]) }}</strong>
                        </div>
                        <div class="bar-track">
                            <span class="bar-fill" style="width: {{ $barPercent($summary['executions'][$state], $summary['executions']['total']) }}"></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="dashboard-layout" aria-label="Recent runtime tables">
        <div class="dashboard-panel">
            <span class="panel-kicker">Recent tasks</span>
            <h3>Queue movement</h3>
            @if (count($recentTasks) > 0)
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>State</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentTasks as $task)
                            <tr>
                                <td>{{ $task['title'] }}</td>
                                <td><span class="pill {{ in_array($task['state'], ['queued', 'completed'], true) ? 'good' : ($task['state'] === 'failed' ? 'bad' : '') }}">{{ $task['state'] }}</span></td>
                                <td>{{ $task['requested_agent_role'] ?: 'Any' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="empty-state">No tasks exist yet. Intake activity will appear here.</p>
            @endif
        </div>

        <div class="dashboard-panel">
            <span class="panel-kicker">Recent executions</span>
            <h3>Worker outcomes</h3>
            @if (count($recentExecutions) > 0)
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Execution</th>
                            <th>Status</th>
                            <th>Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentExecutions as $execution)
                            <tr>
                                <td>{{ $execution['task_title'] ?: $execution['id'] }}</td>
                                <td><span class="pill {{ in_array($execution['status'], ['running', 'succeeded'], true) ? 'good' : ($execution['status'] === 'failed' ? 'bad' : '') }}">{{ $execution['status'] }}</span></td>
                                <td>{{ $execution['agent_name'] ?: $execution['agent_id'] ?: 'Unassigned' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="empty-state">No executions exist yet. Worker activity will appear here.</p>
            @endif
        </div>
    </section>
@endsection

@section('pageScripts')
    <script>
        (() => {
            const bootstrap = window.OfficeAdmin || {};
            const dashboard = bootstrap.dashboardMetrics || {};

            window.OfficeDashboard = {
                summaryEndpoint: dashboard.summary,
                tasksEndpoint: dashboard.tasks,
                executionsEndpoint: dashboard.executions,
                auditEventsEndpoint: dashboard.auditEvents,
                refreshIntervalMs: dashboard.refreshIntervalMs || 30000,
                initialSummary: bootstrap.initialSummary || {},
                recentTasks: bootstrap.recentTasks || [],
                recentExecutions: bootstrap.recentExecutions || [],
            };
        })();
    </script>
@endsection
