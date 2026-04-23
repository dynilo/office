@extends('admin.layout')

@section('pageStyles')
    <style>
        .execution-workspace {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(22rem, 0.9fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .execution-panel {
            padding: 1.25rem;
            border: 1px solid rgba(20, 33, 61, 0.08);
            border-radius: 1.25rem;
            background: rgba(255, 253, 247, 0.84);
            box-shadow: 0 16px 40px rgba(20, 33, 61, 0.06);
        }

        .execution-panel h3 {
            margin: 0 0 0.35rem;
            font-size: 1.15rem;
        }

        .execution-panel p,
        .empty-state {
            color: var(--ink-soft);
            line-height: 1.55;
        }

        .monitor-toolbar {
            display: grid;
            grid-template-columns: minmax(12rem, 1fr) minmax(8rem, 0.35fr) auto auto;
            gap: 0.7rem;
            margin: 1rem 0;
        }

        .field {
            display: grid;
            gap: 0.35rem;
        }

        .field label {
            color: var(--ink-soft);
            font-size: 0.76rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .field input,
        .field select {
            width: 100%;
            padding: 0.72rem 0.8rem;
            border: 1px solid rgba(20, 33, 61, 0.14);
            border-radius: 0.85rem;
            color: var(--ink);
            background: rgba(255, 255, 255, 0.74);
            font: inherit;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.75rem;
            padding: 0.72rem 1rem;
            border: 1px solid rgba(196, 106, 45, 0.36);
            border-radius: 999px;
            color: #fffdf7;
            background: var(--accent);
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: transform 140ms ease, box-shadow 140ms ease;
        }

        .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(196, 106, 45, 0.2);
        }

        .button.secondary {
            color: var(--ink);
            background: rgba(255, 255, 255, 0.72);
        }

        .execution-list {
            display: grid;
            gap: 0.75rem;
        }

        .execution-card {
            display: grid;
            gap: 0.65rem;
            width: 100%;
            padding: 1rem;
            border: 1px solid rgba(20, 33, 61, 0.09);
            border-radius: 1rem;
            color: inherit;
            text-align: left;
            background: rgba(255, 255, 255, 0.62);
            cursor: pointer;
        }

        .execution-card.is-selected {
            border-color: rgba(196, 106, 45, 0.45);
            background: rgba(241, 215, 196, 0.42);
        }

        .execution-card h4 {
            margin: 0;
            font-size: 1.02rem;
            overflow-wrap: anywhere;
        }

        .execution-card p {
            margin: 0;
            color: var(--ink-soft);
            overflow-wrap: anywhere;
        }

        .execution-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
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

        .pill.status-running,
        .pill.status-succeeded {
            color: var(--ok);
            background: rgba(47, 107, 79, 0.12);
        }

        .pill.status-failed,
        .pill.level-error {
            color: #8b2d22;
            background: rgba(139, 45, 34, 0.12);
        }

        .pill.level-warning {
            color: #84510f;
            background: rgba(196, 106, 45, 0.13);
        }

        .execution-detail {
            position: sticky;
            top: 1rem;
            align-self: start;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
            margin: 1rem 0;
        }

        .detail-item {
            padding: 0.8rem;
            border: 1px solid rgba(20, 33, 61, 0.08);
            border-radius: 0.9rem;
            background: rgba(255, 255, 255, 0.58);
        }

        .detail-item span {
            display: block;
            color: var(--ink-soft);
            font-size: 0.76rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .detail-item strong {
            display: block;
            margin-top: 0.25rem;
            overflow-wrap: anywhere;
        }

        .log-stream {
            display: grid;
            gap: 0.65rem;
            max-height: 22rem;
            overflow: auto;
            padding-right: 0.25rem;
        }

        .log-line {
            display: grid;
            gap: 0.45rem;
            padding: 0.85rem;
            border: 1px solid rgba(20, 33, 61, 0.08);
            border-radius: 0.9rem;
            background: rgba(20, 33, 61, 0.04);
        }

        .log-head {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            align-items: center;
        }

        .log-line p {
            margin: 0;
            color: var(--ink);
        }

        .payload-box {
            overflow: auto;
            max-height: 12rem;
            padding: 1rem;
            border-radius: 1rem;
            color: #233047;
            background: rgba(20, 33, 61, 0.06);
            font-family: "Courier New", monospace;
            font-size: 0.86rem;
            line-height: 1.45;
            white-space: pre-wrap;
        }

        .notice {
            display: none;
            margin-top: 1rem;
            padding: 0.75rem 0.9rem;
            border-radius: 0.9rem;
            color: var(--ok);
            background: rgba(47, 107, 79, 0.12);
        }

        .notice.is-error {
            color: #8b2d22;
            background: rgba(139, 45, 34, 0.12);
        }

        .notice.is-visible {
            display: block;
        }

        @media (max-width: 1100px) {
            .execution-workspace,
            .monitor-toolbar {
                grid-template-columns: 1fr;
            }

            .execution-detail {
                position: static;
            }
        }
    </style>
@endsection

@section('content')
    <section class="hero">
        <span class="status">Execution monitor active</span>
        <h2>Watch execution lifecycle and logs.</h2>
        <p>
            This monitor lists execution records from the existing admin API and exposes execution detail plus
            persisted logs from the current runtime data. Refresh is static/polled; websocket streaming is out of scope.
        </p>
    </section>

    <section class="execution-workspace" aria-label="Execution monitor workspace">
        <div class="execution-panel">
            <h3>Executions</h3>
            <p>Filter by status or identifier, inspect one execution, and refresh the list from the admin API.</p>

            <div class="monitor-toolbar">
                <div class="field">
                    <label for="execution-search">Search</label>
                    <input id="execution-search" type="search" placeholder="Execution, task, agent">
                </div>
                <div class="field">
                    <label for="execution-status-filter">Status</label>
                    <select id="execution-status-filter">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="running">Running</option>
                        <option value="succeeded">Succeeded</option>
                        <option value="failed">Failed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <button class="button secondary" type="button" id="execution-refresh">Refresh</button>
                <button class="button secondary" type="button" id="execution-poll">Start polling</button>
            </div>

            <div id="execution-list" class="execution-list">
                @forelse ($executions as $execution)
                    <button
                        type="button"
                        class="execution-card {{ $loop->first ? 'is-selected' : '' }}"
                        data-action="inspect"
                        data-execution-id="{{ $execution['id'] }}"
                    >
                        <div class="execution-meta">
                            <span class="pill status-{{ $execution['status'] }}">{{ $execution['status'] }}</span>
                            <span class="pill">attempt {{ $execution['attempt'] }}</span>
                            <span class="pill">{{ count($execution['logs']) }} logs</span>
                        </div>
                        <h4>{{ $execution['task_title'] ?: $execution['task_id'] }}</h4>
                        <p>{{ $execution['agent_name'] ?: $execution['agent_id'] ?: 'Unassigned agent' }}</p>
                    </button>
                @empty
                    <p class="empty-state">No executions exist yet. Runtime activity will appear here after tasks are executed.</p>
                @endforelse
            </div>

            <div id="execution-notice" class="notice" role="status"></div>
        </div>

        <aside class="execution-panel execution-detail" aria-live="polite">
            <h3>Execution details</h3>
            <div id="execution-detail">
                @if (count($executions) > 0)
                    @php($selectedExecution = $executions[0])
                    <div class="detail-grid">
                        <div class="detail-item"><span>Status</span><strong>{{ $selectedExecution['status'] }}</strong></div>
                        <div class="detail-item"><span>Attempt</span><strong>{{ $selectedExecution['attempt'] }}</strong></div>
                        <div class="detail-item"><span>Task</span><strong>{{ $selectedExecution['task_title'] ?: $selectedExecution['task_id'] }}</strong></div>
                        <div class="detail-item"><span>Agent</span><strong>{{ $selectedExecution['agent_name'] ?: $selectedExecution['agent_id'] ?: 'Unassigned' }}</strong></div>
                        <div class="detail-item"><span>Retries</span><strong>{{ $selectedExecution['retry_count'] }} / {{ $selectedExecution['max_retries'] }}</strong></div>
                        <div class="detail-item"><span>Failure class</span><strong>{{ $selectedExecution['failure_classification'] ?: 'None' }}</strong></div>
                    </div>

                    @if ($selectedExecution['error_message'])
                        <h4>Error message</h4>
                        <pre class="payload-box">{{ $selectedExecution['error_message'] }}</pre>
                    @endif

                    <h4>Execution log stream</h4>
                    <div class="log-stream">
                        @forelse ($selectedExecution['logs'] as $log)
                            <article class="log-line">
                                <div class="log-head">
                                    <span class="pill level-{{ $log['level'] }}">{{ $log['level'] }}</span>
                                    <span class="pill">#{{ $log['sequence'] }}</span>
                                    <span class="pill">{{ $log['logged_at'] }}</span>
                                </div>
                                <p>{{ $log['message'] }}</p>
                                <pre class="payload-box">{{ json_encode($log['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </article>
                        @empty
                            <p class="empty-state">No logs have been written for this execution yet.</p>
                        @endforelse
                    </div>
                @else
                    <p class="empty-state">Select an execution to inspect its state, retry counters, and log stream.</p>
                @endif
            </div>
        </aside>
    </section>
@endsection

@section('pageScripts')
    <script>
        (() => {
            const bootstrap = window.OfficeAdmin || {};
            const monitor = bootstrap.executionMonitor || {};
            let executions = Array.isArray(bootstrap.initialExecutions) ? bootstrap.initialExecutions : [];
            let selectedExecutionId = executions[0]?.id || null;
            let pollTimer = null;

            const list = document.querySelector('#execution-list');
            const detail = document.querySelector('#execution-detail');
            const search = document.querySelector('#execution-search');
            const statusFilter = document.querySelector('#execution-status-filter');
            const refresh = document.querySelector('#execution-refresh');
            const poll = document.querySelector('#execution-poll');
            const notice = document.querySelector('#execution-notice');

            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[char]));

            const prettyDate = (value) => {
                if (!value) {
                    return 'Not recorded';
                }

                return new Date(value).toLocaleString();
            };

            const normalizeExecution = (execution) => {
                const previous = executions.find((candidate) => candidate.id === execution.id) || {};

                return {
                    ...previous,
                    ...execution,
                    status: execution.status || previous.status || 'pending',
                    attempt: execution.attempt ?? previous.attempt ?? 1,
                    retry_count: execution.retry_count ?? previous.retry_count ?? 0,
                    max_retries: execution.max_retries ?? previous.max_retries ?? 0,
                    logs: Array.isArray(execution.logs) ? execution.logs : (previous.logs || []),
                };
            };

            const setNotice = (message, isError = false) => {
                notice.textContent = message;
                notice.classList.toggle('is-error', isError);
                notice.classList.add('is-visible');
            };

            const filteredExecutions = () => {
                const term = search.value.trim().toLowerCase();
                const status = statusFilter.value;

                return executions.filter((execution) => {
                    const haystack = [
                        execution.id,
                        execution.task_id,
                        execution.task_title,
                        execution.agent_id,
                        execution.agent_name,
                        execution.status,
                    ].join(' ').toLowerCase();

                    if (term && !haystack.includes(term)) {
                        return false;
                    }

                    if (status && execution.status !== status) {
                        return false;
                    }

                    return true;
                });
            };

            const renderExecutions = () => {
                const visible = filteredExecutions();

                if (visible.length === 0) {
                    list.innerHTML = '<p class="empty-state">No executions match the current filters.</p>';
                    return;
                }

                list.innerHTML = visible.map((execution) => `
                    <button type="button" class="execution-card ${execution.id === selectedExecutionId ? 'is-selected' : ''}" data-action="inspect" data-execution-id="${escapeHtml(execution.id)}">
                        <div class="execution-meta">
                            <span class="pill status-${escapeHtml(execution.status)}">${escapeHtml(execution.status)}</span>
                            <span class="pill">attempt ${escapeHtml(execution.attempt)}</span>
                            <span class="pill">${escapeHtml(execution.logs.length)} logs</span>
                        </div>
                        <h4>${escapeHtml(execution.task_title || execution.task_id)}</h4>
                        <p>${escapeHtml(execution.agent_name || execution.agent_id || 'Unassigned agent')}</p>
                    </button>
                `).join('');
            };

            const renderLogs = (logs) => {
                if (!logs.length) {
                    return '<p class="empty-state">No logs have been written for this execution yet.</p>';
                }

                return logs.map((log) => `
                    <article class="log-line">
                        <div class="log-head">
                            <span class="pill level-${escapeHtml(log.level)}">${escapeHtml(log.level)}</span>
                            <span class="pill">#${escapeHtml(log.sequence)}</span>
                            <span class="pill">${escapeHtml(prettyDate(log.logged_at))}</span>
                        </div>
                        <p>${escapeHtml(log.message)}</p>
                        <pre class="payload-box">${escapeHtml(JSON.stringify(log.context || {}, null, 2))}</pre>
                    </article>
                `).join('');
            };

            const renderDetail = (execution) => {
                if (!execution) {
                    detail.innerHTML = '<p class="empty-state">Select an execution to inspect its state, retry counters, and log stream.</p>';
                    return;
                }

                detail.innerHTML = `
                    <div class="detail-grid">
                        <div class="detail-item"><span>Status</span><strong>${escapeHtml(execution.status)}</strong></div>
                        <div class="detail-item"><span>Attempt</span><strong>${escapeHtml(execution.attempt)}</strong></div>
                        <div class="detail-item"><span>Task</span><strong>${escapeHtml(execution.task_title || execution.task_id)}</strong></div>
                        <div class="detail-item"><span>Agent</span><strong>${escapeHtml(execution.agent_name || execution.agent_id || 'Unassigned')}</strong></div>
                        <div class="detail-item"><span>Retries</span><strong>${escapeHtml(execution.retry_count)} / ${escapeHtml(execution.max_retries)}</strong></div>
                        <div class="detail-item"><span>Failure class</span><strong>${escapeHtml(execution.failure_classification || 'None')}</strong></div>
                        <div class="detail-item"><span>Started</span><strong>${escapeHtml(prettyDate(execution.started_at))}</strong></div>
                        <div class="detail-item"><span>Finished</span><strong>${escapeHtml(prettyDate(execution.finished_at))}</strong></div>
                    </div>
                    ${execution.error_message ? `<h4>Error message</h4><pre class="payload-box">${escapeHtml(execution.error_message)}</pre>` : ''}
                    <h4>Execution log stream</h4>
                    <div class="log-stream">${renderLogs(execution.logs || [])}</div>
                `;
            };

            const requestJson = async (url) => {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(body.message || 'The execution API request failed.');
                }

                return body;
            };

            const loadExecutions = async () => {
                const body = await requestJson(`${monitor.list}?sort=created_at&direction=desc&per_page=100`);
                executions = (body.data || []).map(normalizeExecution);
                selectedExecutionId = executions.find((execution) => execution.id === selectedExecutionId)?.id || executions[0]?.id || null;
                renderExecutions();
                renderDetail(executions.find((execution) => execution.id === selectedExecutionId));
            };

            search.addEventListener('input', renderExecutions);
            statusFilter.addEventListener('change', renderExecutions);

            refresh.addEventListener('click', async () => {
                try {
                    await loadExecutions();
                    setNotice('Execution list refreshed from the admin API.');
                } catch (error) {
                    setNotice(error.message, true);
                }
            });

            poll.addEventListener('click', () => {
                if (pollTimer) {
                    window.clearInterval(pollTimer);
                    pollTimer = null;
                    poll.textContent = 'Start polling';
                    setNotice('Execution polling stopped.');
                    return;
                }

                pollTimer = window.setInterval(() => {
                    loadExecutions().catch((error) => setNotice(error.message, true));
                }, monitor.refreshIntervalMs || 15000);
                poll.textContent = 'Stop polling';
                setNotice('Execution polling started.');
            });

            list.addEventListener('click', (event) => {
                const button = event.target.closest('[data-action="inspect"]');

                if (!button) {
                    return;
                }

                selectedExecutionId = button.dataset.executionId;
                renderExecutions();
                renderDetail(executions.find((execution) => execution.id === selectedExecutionId));
            });

            renderExecutions();
            renderDetail(executions.find((execution) => execution.id === selectedExecutionId));
        })();
    </script>
@endsection
