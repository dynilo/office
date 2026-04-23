@extends('admin.layout')

@section('pageStyles')
    <style>
        .tasks-workspace {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(20rem, 0.85fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .tasks-panel {
            padding: 1.25rem;
            border: 1px solid rgba(20, 33, 61, 0.08);
            border-radius: 1.25rem;
            background: rgba(255, 253, 247, 0.82);
            box-shadow: 0 16px 40px rgba(20, 33, 61, 0.06);
        }

        .tasks-panel h3,
        .task-form h3 {
            margin: 0 0 0.35rem;
            font-size: 1.15rem;
        }

        .tasks-panel p,
        .task-form p,
        .empty-state {
            color: var(--ink-soft);
            line-height: 1.55;
        }

        .queue-toolbar {
            display: grid;
            grid-template-columns: minmax(12rem, 1fr) repeat(2, minmax(8rem, 0.45fr)) auto;
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
        .field select,
        .field textarea {
            width: 100%;
            padding: 0.72rem 0.8rem;
            border: 1px solid rgba(20, 33, 61, 0.14);
            border-radius: 0.85rem;
            color: var(--ink);
            background: rgba(255, 255, 255, 0.74);
            font: inherit;
        }

        .field textarea {
            min-height: 7.5rem;
            resize: vertical;
            font-family: "Courier New", monospace;
            font-size: 0.88rem;
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

        .task-list {
            display: grid;
            gap: 0.75rem;
        }

        .task-card {
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

        .task-card.is-selected {
            border-color: rgba(196, 106, 45, 0.45);
            background: rgba(241, 215, 196, 0.42);
        }

        .task-card h4 {
            margin: 0;
            font-size: 1.05rem;
        }

        .task-card p {
            margin: 0;
            color: var(--ink-soft);
        }

        .task-meta {
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

        .pill.state-queued,
        .pill.state-completed {
            color: var(--ok);
            background: rgba(47, 107, 79, 0.12);
        }

        .pill.state-failed,
        .pill.priority-critical {
            color: #8b2d22;
            background: rgba(139, 45, 34, 0.12);
        }

        .task-details {
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

        .payload-box {
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

        .unified-sections {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }

        .unified-section {
            padding-top: 1rem;
            border-top: 1px solid rgba(20, 33, 61, 0.08);
        }

        .unified-section h4 {
            margin: 0 0 0.65rem;
        }

        .linked-list {
            display: grid;
            gap: 0.7rem;
        }

        .linked-card {
            display: grid;
            gap: 0.45rem;
            padding: 0.85rem;
            border: 1px solid rgba(20, 33, 61, 0.08);
            border-radius: 0.9rem;
            background: rgba(255, 255, 255, 0.58);
        }

        .linked-card p {
            margin: 0;
            color: var(--ink-soft);
            overflow-wrap: anywhere;
        }

        .linked-head {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            align-items: center;
        }

        .task-form {
            margin-top: 1rem;
            padding: 1.25rem;
            border: 1px solid rgba(20, 33, 61, 0.08);
            border-radius: 1.25rem;
            background: rgba(255, 253, 247, 0.82);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem;
            margin-top: 1rem;
        }

        .form-grid .span-2 {
            grid-column: 1 / -1;
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
            .tasks-workspace,
            .queue-toolbar,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .task-details {
                position: static;
            }
        }
    </style>
@endsection

@section('content')
    <section class="hero">
        <span class="status">Task queue active</span>
        <h2>Intake, filter, and inspect runtime tasks.</h2>
        <p>
            This admin page uses the existing task and admin APIs to view queued work, inspect task payloads,
            and create draft or queued tasks without touching execution monitoring.
        </p>
    </section>

    <section class="tasks-workspace" aria-label="Task queue workspace">
        <div class="tasks-panel">
            <h3>Task queue</h3>
            <p>Filter locally, refresh from the admin task API, and open a task to inspect its stored context.</p>

            <div class="queue-toolbar">
                <div class="field">
                    <label for="task-search">Search</label>
                    <input id="task-search" type="search" placeholder="Title, role, source">
                </div>
                <div class="field">
                    <label for="task-state-filter">State</label>
                    <select id="task-state-filter">
                        <option value="">All states</option>
                        <option value="draft">Draft</option>
                        <option value="queued">Queued</option>
                        <option value="in_progress">In progress</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="field">
                    <label for="task-role-filter">Role</label>
                    <input id="task-role-filter" type="search" placeholder="research">
                </div>
                <button class="button secondary" type="button" id="task-refresh">Refresh</button>
            </div>

            <div id="task-list" class="task-list">
                @forelse ($tasks as $task)
                    <button
                        type="button"
                        class="task-card {{ $loop->first ? 'is-selected' : '' }}"
                        data-action="inspect"
                        data-task-id="{{ $task['id'] }}"
                    >
                        <div class="task-meta">
                            <span class="pill state-{{ $task['state'] }}">{{ $task['state'] }}</span>
                            <span class="pill priority-{{ $task['priority'] }}">{{ $task['priority'] }}</span>
                            @if ($task['requested_agent_role'])
                                <span class="pill">{{ $task['requested_agent_role'] }}</span>
                            @endif
                        </div>
                        <h4>{{ $task['title'] }}</h4>
                        <p>{{ $task['summary'] ?: 'No summary provided.' }}</p>
                    </button>
                @empty
                    <p class="empty-state">No tasks exist yet. Create a draft or queued task to start the intake flow.</p>
                @endforelse
            </div>
        </div>

        <aside class="tasks-panel task-details" aria-live="polite">
            <h3>Task details</h3>
            <div id="task-detail">
                @if (count($tasks) > 0)
                    @php($selectedTask = $tasks[0])
                    <h4>{{ $selectedTask['title'] }}</h4>
                    <p>{{ $selectedTask['description'] ?: $selectedTask['summary'] ?: 'No description provided.' }}</p>
                    <div class="detail-grid">
                        <div class="detail-item"><span>State</span><strong>{{ $selectedTask['state'] }}</strong></div>
                        <div class="detail-item"><span>Priority</span><strong>{{ $selectedTask['priority'] }}</strong></div>
                        <div class="detail-item"><span>Requested role</span><strong>{{ $selectedTask['requested_agent_role'] ?: 'Any' }}</strong></div>
                        <div class="detail-item"><span>Source</span><strong>{{ $selectedTask['source'] ?: 'Unspecified' }}</strong></div>
                    </div>
                    <h4>Payload JSON</h4>
                    <pre class="payload-box">{{ json_encode($selectedTask['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    <div class="unified-sections">
                        <div class="unified-section">
                            <h4>Executions</h4>
                            <div class="linked-list">
                                @forelse ($selectedTask['executions'] ?? [] as $execution)
                                    <article class="linked-card">
                                        <div class="linked-head">
                                            <span class="pill state-{{ $execution['status'] }}">{{ $execution['status'] }}</span>
                                            <span class="pill">attempt {{ $execution['attempt'] }}</span>
                                            <span class="pill">{{ count($execution['logs']) }} logs</span>
                                        </div>
                                        <p>{{ $execution['agent_name'] ?: $execution['agent_id'] ?: 'Unassigned agent' }}</p>
                                    </article>
                                @empty
                                    <p class="empty-state">No executions are linked to this task yet.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="unified-section">
                            <h4>Artifacts</h4>
                            <div class="linked-list">
                                @forelse ($selectedTask['artifacts'] ?? [] as $artifact)
                                    <article class="linked-card">
                                        <div class="linked-head">
                                            <span class="pill">{{ $artifact['kind'] }}</span>
                                            <span class="pill">{{ $artifact['name'] }}</span>
                                        </div>
                                        <p>{{ $artifact['content_text'] ?: json_encode($artifact['content_json'] ?: $artifact['file_metadata']) }}</p>
                                    </article>
                                @empty
                                    <p class="empty-state">No artifacts are linked to this task yet.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="unified-section">
                            <h4>Audit events</h4>
                            <div class="linked-list">
                                @forelse ($selectedTask['audit_events'] ?? [] as $event)
                                    <article class="linked-card">
                                        <div class="linked-head">
                                            <span class="pill">{{ $event['event_name'] }}</span>
                                            <span class="pill">{{ $event['source'] }}</span>
                                        </div>
                                        <p>{{ $event['actor_type'] }}: {{ $event['actor_id'] }}</p>
                                    </article>
                                @empty
                                    <p class="empty-state">No audit events are linked to this task yet.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="unified-section">
                            <h4>Communication history</h4>
                            <div class="linked-list">
                                @forelse ($selectedTask['communications'] ?? [] as $message)
                                    <article class="linked-card">
                                        <div class="linked-head">
                                            <span class="pill">{{ $message['message_type'] }}</span>
                                            <span class="pill">{{ $message['sender_name'] ?: $message['sender_agent_id'] }} → {{ $message['recipient_name'] ?: $message['recipient_agent_id'] }}</span>
                                        </div>
                                        <p>{{ $message['subject'] ?: $message['body'] }}</p>
                                    </article>
                                @empty
                                    <p class="empty-state">No agent communication has been logged for this task yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @else
                    <p class="empty-state">Select or create a task to inspect its details and payload JSON.</p>
                @endif
            </div>
        </aside>
    </section>

    <section class="task-form" aria-label="Create task form">
        <h3>Create task</h3>
        <p>Tasks can enter the system as draft or queued. Assignment and execution remain outside this UI slice.</p>

        <form id="task-create-form">
            <div class="form-grid">
                <div class="field span-2">
                    <label for="task-title">Title</label>
                    <input id="task-title" name="title" type="text" maxlength="255" required>
                </div>
                <div class="field span-2">
                    <label for="task-summary">Summary</label>
                    <input id="task-summary" name="summary" type="text" maxlength="500">
                </div>
                <div class="field span-2">
                    <label for="task-description">Description</label>
                    <textarea id="task-description" name="description"></textarea>
                </div>
                <div class="field">
                    <label for="task-priority">Priority</label>
                    <select id="task-priority" name="priority" required>
                        <option value="normal">Normal</option>
                        <option value="low">Low</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="field">
                    <label for="task-initial-state">Initial state</label>
                    <select id="task-initial-state" name="initial_state" required>
                        <option value="queued">Queued</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <div class="field">
                    <label for="task-source">Source</label>
                    <input id="task-source" name="source" type="text" maxlength="100" placeholder="admin">
                </div>
                <div class="field">
                    <label for="task-requested-role">Requested agent role</label>
                    <input id="task-requested-role" name="requested_agent_role" type="text" maxlength="100" placeholder="research">
                </div>
                <div class="field">
                    <label for="task-due-at">Due at</label>
                    <input id="task-due-at" name="due_at" type="datetime-local">
                </div>
                <div class="field span-2">
                    <label for="task-payload">Payload JSON</label>
                    <textarea id="task-payload" name="payload" required>{
  "request": "Research market signals",
  "channel": "admin"
}</textarea>
                </div>
            </div>

            <button class="button" type="submit">Create task</button>
            <div id="task-notice" class="notice" role="status"></div>
        </form>
    </section>
@endsection

@section('pageScripts')
    <script>
        (() => {
            const bootstrap = window.OfficeAdmin || {};
            const endpoints = bootstrap.taskQueue || {};
            let tasks = Array.isArray(bootstrap.initialTasks) ? bootstrap.initialTasks : [];
            let selectedTaskId = tasks[0]?.id || null;

            const list = document.querySelector('#task-list');
            const detail = document.querySelector('#task-detail');
            const search = document.querySelector('#task-search');
            const stateFilter = document.querySelector('#task-state-filter');
            const roleFilter = document.querySelector('#task-role-filter');
            const refresh = document.querySelector('#task-refresh');
            const form = document.querySelector('#task-create-form');
            const payloadInput = document.querySelector('#task-payload');
            const notice = document.querySelector('#task-notice');

            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[char]));

            const prettyDate = (value) => {
                if (!value) {
                    return 'Unscheduled';
                }

                return new Date(value).toLocaleString();
            };

            const normalizeTask = (task) => ({
                ...task,
                state: task.state || task.status || 'draft',
                priority: task.priority || 'normal',
                payload: task.payload || {},
            });

            const setNotice = (message, isError = false) => {
                notice.textContent = message;
                notice.classList.toggle('is-error', isError);
                notice.classList.add('is-visible');
            };

            const filteredTasks = () => {
                const term = search.value.trim().toLowerCase();
                const state = stateFilter.value;
                const role = roleFilter.value.trim().toLowerCase();

                return tasks.filter((task) => {
                    const haystack = [
                        task.title,
                        task.summary,
                        task.source,
                        task.requested_agent_role,
                        task.state,
                        task.priority,
                    ].join(' ').toLowerCase();

                    if (term && !haystack.includes(term)) {
                        return false;
                    }

                    if (state && task.state !== state) {
                        return false;
                    }

                    if (role && !String(task.requested_agent_role || '').toLowerCase().includes(role)) {
                        return false;
                    }

                    return true;
                });
            };

            const renderTasks = () => {
                const visible = filteredTasks();

                if (visible.length === 0) {
                    list.innerHTML = '<p class="empty-state">No tasks match the current filters.</p>';
                    return;
                }

                list.innerHTML = visible.map((task) => `
                    <button type="button" class="task-card ${task.id === selectedTaskId ? 'is-selected' : ''}" data-action="inspect" data-task-id="${escapeHtml(task.id)}">
                        <div class="task-meta">
                            <span class="pill state-${escapeHtml(task.state)}">${escapeHtml(task.state)}</span>
                            <span class="pill priority-${escapeHtml(task.priority)}">${escapeHtml(task.priority)}</span>
                            ${task.requested_agent_role ? `<span class="pill">${escapeHtml(task.requested_agent_role)}</span>` : ''}
                        </div>
                        <h4>${escapeHtml(task.title)}</h4>
                        <p>${escapeHtml(task.summary || 'No summary provided.')}</p>
                    </button>
                `).join('');
            };

            const renderDetail = (task) => {
                if (!task) {
                    detail.innerHTML = '<p class="empty-state">Select or create a task to inspect its details and payload JSON.</p>';
                    return;
                }

                detail.innerHTML = `
                    <h4>${escapeHtml(task.title)}</h4>
                    <p>${escapeHtml(task.description || task.summary || 'No description provided.')}</p>
                    <div class="detail-grid">
                        <div class="detail-item"><span>State</span><strong>${escapeHtml(task.state)}</strong></div>
                        <div class="detail-item"><span>Priority</span><strong>${escapeHtml(task.priority)}</strong></div>
                        <div class="detail-item"><span>Requested role</span><strong>${escapeHtml(task.requested_agent_role || 'Any')}</strong></div>
                        <div class="detail-item"><span>Source</span><strong>${escapeHtml(task.source || 'Unspecified')}</strong></div>
                        <div class="detail-item"><span>Agent</span><strong>${escapeHtml(task.agent_name || task.agent_id || 'Unassigned')}</strong></div>
                        <div class="detail-item"><span>Due at</span><strong>${escapeHtml(prettyDate(task.due_at))}</strong></div>
                    </div>
                    <h4>Payload JSON</h4>
                    <pre class="payload-box">${escapeHtml(JSON.stringify(task.payload || {}, null, 2))}</pre>
                    <div class="unified-sections">
                        ${renderExecutions(task.executions || [])}
                        ${renderArtifacts(task.artifacts || [])}
                        ${renderAuditEvents(task.audit_events || [])}
                        ${renderCommunications(task.communications || [])}
                    </div>
                `;
            };

            const renderExecutions = (executions) => `
                <div class="unified-section">
                    <h4>Executions</h4>
                    <div class="linked-list">
                        ${executions.length ? executions.map((execution) => `
                            <article class="linked-card">
                                <div class="linked-head">
                                    <span class="pill state-${escapeHtml(execution.status)}">${escapeHtml(execution.status)}</span>
                                    <span class="pill">attempt ${escapeHtml(execution.attempt)}</span>
                                    <span class="pill">${escapeHtml((execution.logs || []).length)} logs</span>
                                </div>
                                <p>${escapeHtml(execution.agent_name || execution.agent_id || 'Unassigned agent')}</p>
                                ${execution.error_message ? `<pre class="payload-box">${escapeHtml(execution.error_message)}</pre>` : ''}
                            </article>
                        `).join('') : '<p class="empty-state">No executions are linked to this task yet.</p>'}
                    </div>
                </div>
            `;

            const renderArtifacts = (artifacts) => `
                <div class="unified-section">
                    <h4>Artifacts</h4>
                    <div class="linked-list">
                        ${artifacts.length ? artifacts.map((artifact) => `
                            <article class="linked-card">
                                <div class="linked-head">
                                    <span class="pill">${escapeHtml(artifact.kind)}</span>
                                    <span class="pill">${escapeHtml(artifact.name)}</span>
                                </div>
                                <p>${escapeHtml(artifact.content_text || JSON.stringify(artifact.content_json || artifact.file_metadata || {}))}</p>
                            </article>
                        `).join('') : '<p class="empty-state">No artifacts are linked to this task yet.</p>'}
                    </div>
                </div>
            `;

            const renderAuditEvents = (events) => `
                <div class="unified-section">
                    <h4>Audit events</h4>
                    <div class="linked-list">
                        ${events.length ? events.map((event) => `
                            <article class="linked-card">
                                <div class="linked-head">
                                    <span class="pill">${escapeHtml(event.event_name)}</span>
                                    <span class="pill">${escapeHtml(event.source || 'unknown source')}</span>
                                </div>
                                <p>${escapeHtml(event.actor_type || 'actor')}: ${escapeHtml(event.actor_id || 'unknown')}</p>
                            </article>
                        `).join('') : '<p class="empty-state">No audit events are linked to this task yet.</p>'}
                    </div>
                </div>
            `;

            const renderCommunications = (messages) => `
                <div class="unified-section">
                    <h4>Communication history</h4>
                    <div class="linked-list">
                        ${messages.length ? messages.map((message) => `
                            <article class="linked-card">
                                <div class="linked-head">
                                    <span class="pill">${escapeHtml(message.message_type)}</span>
                                    <span class="pill">${escapeHtml(message.sender_name || message.sender_agent_id)} → ${escapeHtml(message.recipient_name || message.recipient_agent_id)}</span>
                                </div>
                                <p>${escapeHtml(message.subject || message.body)}</p>
                            </article>
                        `).join('') : '<p class="empty-state">No agent communication has been logged for this task yet.</p>'}
                    </div>
                </div>
            `;

            const upsertTask = (task) => {
                const normalized = normalizeTask(task);
                const index = tasks.findIndex((candidate) => candidate.id === normalized.id);

                if (index === -1) {
                    tasks = [normalized, ...tasks];
                    return normalized;
                }

                tasks[index] = normalized;
                return normalized;
            };

            const requestJson = async (url, options = {}) => {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    ...options,
                });

                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const message = body.message || 'The task API request failed.';
                    throw new Error(message);
                }

                return body;
            };

            const loadTasks = async () => {
                const body = await requestJson(`${endpoints.list}?sort=created_at&direction=desc&per_page=100`);
                tasks = (body.data || []).map(normalizeTask);
                selectedTaskId = tasks.find((task) => task.id === selectedTaskId)?.id || tasks[0]?.id || null;
                renderTasks();
                renderDetail(tasks.find((task) => task.id === selectedTaskId));
            };

            const inspectTask = async (taskId) => {
                selectedTaskId = taskId;
                renderTasks();

                const body = await requestJson(`${endpoints.show}/${taskId}`);
                const task = upsertTask(body.data || body);
                selectedTaskId = task.id;
                renderTasks();
                renderDetail(task);
            };

            search.addEventListener('input', renderTasks);
            stateFilter.addEventListener('change', renderTasks);
            roleFilter.addEventListener('input', renderTasks);

            refresh.addEventListener('click', async () => {
                try {
                    await loadTasks();
                    setNotice('Task queue refreshed.');
                } catch (error) {
                    setNotice(error.message, true);
                }
            });

            list.addEventListener('click', async (event) => {
                const button = event.target.closest('[data-action="inspect"]');

                if (!button) {
                    return;
                }

                try {
                    await inspectTask(button.dataset.taskId);
                } catch (error) {
                    setNotice(error.message, true);
                }
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                let payload;

                try {
                    payload = JSON.parse(payloadInput.value);
                } catch (error) {
                    setNotice('Payload JSON must be valid JSON.', true);
                    return;
                }

                const formData = new FormData(form);
                const body = {
                    title: formData.get('title'),
                    summary: formData.get('summary') || null,
                    description: formData.get('description') || null,
                    payload,
                    priority: formData.get('priority'),
                    source: formData.get('source') || null,
                    requested_agent_role: formData.get('requested_agent_role') || null,
                    due_at: formData.get('due_at') || null,
                    initial_state: formData.get('initial_state'),
                };

                try {
                    const response = await requestJson(endpoints.create, {
                        method: 'POST',
                        body: JSON.stringify(body),
                    });
                    const task = upsertTask(response.data || response);
                    selectedTaskId = task.id;
                    renderTasks();
                    renderDetail(task);
                    form.reset();
                    payloadInput.value = '{\n  "request": "Research market signals",\n  "channel": "admin"\n}';
                    setNotice('Task created and added to the queue view.');
                } catch (error) {
                    setNotice(error.message, true);
                }
            });

            renderTasks();
            renderDetail(tasks.find((task) => task.id === selectedTaskId));
        })();
    </script>
@endsection
