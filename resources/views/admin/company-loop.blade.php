@extends('admin.layout')

@php
    $goalValue = $oldInput['goal'] ?? '';
    $contextValue = $oldInput['context_json'] ?? "{\n  \"market\": \"regulated enterprise\",\n  \"time_horizon\": \"next quarter\"\n}";
@endphp

@section('pageStyles')
    <style>
        .loop-workspace {
            display: grid;
            grid-template-columns: minmax(0, 0.85fr) minmax(22rem, 1.15fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .loop-panel,
        .loop-form,
        .report-card {
            padding: 1.25rem;
            border: 1px solid rgba(20, 33, 61, 0.08);
            border-radius: 1.25rem;
            background: rgba(255, 253, 247, 0.82);
            box-shadow: 0 16px 40px rgba(20, 33, 61, 0.06);
        }

        .loop-panel h3,
        .loop-form h3,
        .report-card h3 {
            margin: 0 0 0.4rem;
            font-size: 1.15rem;
        }

        .loop-panel p,
        .loop-form p,
        .report-card p,
        .empty-state {
            color: var(--ink-soft);
            line-height: 1.55;
        }

        .field {
            display: grid;
            gap: 0.35rem;
            margin-top: 0.9rem;
        }

        .field label {
            color: var(--ink-soft);
            font-size: 0.76rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .field input,
        .field textarea {
            width: 100%;
            padding: 0.78rem 0.85rem;
            border: 1px solid rgba(20, 33, 61, 0.14);
            border-radius: 0.9rem;
            color: var(--ink);
            background: rgba(255, 255, 255, 0.74);
            font: inherit;
        }

        .field textarea {
            min-height: 8rem;
            resize: vertical;
        }

        .field .json-input {
            font-family: "Courier New", monospace;
            font-size: 0.88rem;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.8rem;
            margin-top: 1rem;
            padding: 0.75rem 1.05rem;
            border: 1px solid rgba(196, 106, 45, 0.36);
            border-radius: 999px;
            color: #fffdf7;
            background: var(--accent);
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .notice {
            margin-top: 1rem;
            padding: 0.8rem 0.95rem;
            border-radius: 0.95rem;
            color: var(--ok);
            background: rgba(47, 107, 79, 0.12);
        }

        .notice.is-error {
            color: #8b2d22;
            background: rgba(139, 45, 34, 0.12);
        }

        .prereq-list,
        .report-grid,
        .child-reports {
            display: grid;
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .prereq-card,
        .child-card,
        .summary-card {
            padding: 0.9rem;
            border: 1px solid rgba(20, 33, 61, 0.08);
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.58);
        }

        .prereq-card strong,
        .child-card strong,
        .summary-card strong {
            display: block;
            overflow-wrap: anywhere;
        }

        .pill-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            margin-top: 0.55rem;
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

        .report-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .json-box {
            overflow: auto;
            max-height: 18rem;
            padding: 1rem;
            border-radius: 1rem;
            color: #233047;
            background: rgba(20, 33, 61, 0.06);
            font-family: "Courier New", monospace;
            font-size: 0.86rem;
            line-height: 1.45;
            white-space: pre-wrap;
        }

        @media (max-width: 1050px) {
            .loop-workspace,
            .report-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    <section class="hero">
        <span class="status">Company loop ready</span>
        <h2>Run a coordinated company loop.</h2>
        <p>
            Submit a high-level operational goal. The existing application service creates coordinator intent,
            decomposes specialist workstreams, executes eligible specialists, persists outputs, and returns a
            structured coordinator report.
        </p>
    </section>

    <section class="loop-workspace" aria-label="Company loop run surface">
        <div>
            <form class="loop-form" method="POST" action="{{ route('admin.company-loop.run') }}">
                @csrf
                <h3>Goal intake</h3>
                <p>Keep the goal explicit enough for strategy, finance, and compliance specialists to produce useful output.</p>

                <div class="field">
                    <label for="goal">High-level goal</label>
                    <textarea id="goal" name="goal" required minlength="10" maxlength="2000" placeholder="Launch a premium AI office package for regulated companies.">{{ old('goal', $goalValue) }}</textarea>
                    @error('goal')
                        <div class="notice is-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="context_json">Optional context JSON</label>
                    <textarea id="context_json" class="json-input" name="context_json">{{ old('context_json', $contextValue) }}</textarea>
                    @error('context_json')
                        <div class="notice is-error">{{ $message }}</div>
                    @enderror
                </div>

                <button class="button" type="submit">Run company loop</button>

                @if ($error)
                    <div class="notice is-error" role="alert">{{ $error }}</div>
                @endif
            </form>

            <div class="loop-panel" style="margin-top: 1rem;">
                <h3>Runtime prerequisites</h3>
                <p>The run requires one active coordinator and active strategy, finance, and compliance specialists.</p>

                <div class="prereq-list">
                    <div class="prereq-card">
                        <span class="pill {{ $coordinator ? 'good' : '' }}">Coordinator</span>
                        <strong>{{ $coordinator?->name ?? 'Missing active coordinator' }}</strong>
                        <p>{{ $coordinator?->role ?? 'Create or activate a coordinator agent before running.' }}</p>
                    </div>

                    @forelse ($specialists as $agent)
                        <div class="prereq-card">
                            <span class="pill good">{{ $agent['role'] }}</span>
                            <strong>{{ $agent['name'] }}</strong>
                            <p>{{ implode(', ', $agent['capabilities']) ?: 'No capabilities declared.' }}</p>
                        </div>
                    @empty
                        <div class="prereq-card">
                            <span class="pill">Specialists</span>
                            <strong>No active specialists found</strong>
                            <p>Active strategy, finance, and legal compliance agents are required.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="report-card">
            <h3>Company loop report</h3>

            @if ($report)
                <p>{{ $report['summary'] }}</p>

                <div class="report-grid">
                    <div class="summary-card">
                        <span class="pill good">Status</span>
                        <strong>{{ $report['status'] }}</strong>
                    </div>
                    <div class="summary-card">
                        <span class="pill">Child tasks</span>
                        <strong>{{ $report['child_task_count'] }}</strong>
                    </div>
                    <div class="summary-card">
                        <span class="pill">Artifact</span>
                        <strong>{{ $report['final_report_artifact_id'] ?? 'not stored' }}</strong>
                    </div>
                </div>

                <div class="child-reports">
                    @foreach ($report['child_reports'] as $child)
                        <article class="child-card">
                            <div class="pill-row">
                                <span class="pill good">{{ $child['agent_role'] ?? 'agent' }}</span>
                                <span class="pill">{{ $child['output_type'] ?? 'output' }}</span>
                                <span class="pill">{{ $child['status'] ?? 'unknown' }}</span>
                            </div>
                            <strong>{{ $child['task_id'] ?? 'task' }}</strong>
                            <p>Execution: {{ $child['execution_id'] ?? 'not created' }}</p>
                            <pre class="json-box">{{ json_encode($child['structured_result'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="empty-state">
                    No company loop has been run in this request. Submit a goal to display the structured coordinator
                    report, child specialist outputs, and persisted artifact reference.
                </p>
            @endif
        </div>
    </section>
@endsection

@section('pageScripts')
    <script>
        window.OfficeCompanyLoop = {
            run: window.OfficeAdmin?.companyLoopRun || {},
            prerequisites: window.OfficeAdmin?.companyLoopPrerequisites || {},
            lastReport: window.OfficeAdmin?.lastReport || null,
            error: window.OfficeAdmin?.companyLoopError || null,
        };
    </script>
@endsection
