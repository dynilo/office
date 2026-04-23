@extends('admin.layout')

@section('pageStyles')
    <style>
        .conversation-workspace {
            display: grid;
            grid-template-columns: minmax(18rem, 0.42fr) minmax(0, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .conversation-panel {
            padding: 1.25rem;
            border: 1px solid rgba(20, 33, 61, 0.08);
            border-radius: 1.25rem;
            background: rgba(255, 253, 247, 0.84);
            box-shadow: 0 16px 40px rgba(20, 33, 61, 0.06);
        }

        .conversation-panel h3 {
            margin: 0 0 0.35rem;
            font-size: 1.15rem;
        }

        .conversation-panel p,
        .empty-state {
            color: var(--ink-soft);
            line-height: 1.55;
        }

        .filter-form {
            display: grid;
            gap: 0.85rem;
            margin-top: 1rem;
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

        .button-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.65rem;
            padding: 0.65rem 1rem;
            border: 1px solid rgba(196, 106, 45, 0.36);
            border-radius: 999px;
            color: #fffdf7;
            background: var(--accent);
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .button.secondary {
            color: var(--ink);
            background: rgba(255, 255, 255, 0.72);
        }

        .conversation-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .summary-card {
            padding: 0.85rem;
            border: 1px solid rgba(20, 33, 61, 0.08);
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.58);
        }

        .summary-card span {
            display: block;
            color: var(--ink-soft);
            font-size: 0.72rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .summary-card strong {
            display: block;
            margin-top: 0.25rem;
            font-size: 1.55rem;
        }

        .timeline {
            display: grid;
            gap: 0.9rem;
        }

        .message-card {
            display: grid;
            gap: 0.75rem;
            padding: 1rem;
            border: 1px solid rgba(20, 33, 61, 0.09);
            border-radius: 1.1rem;
            background: rgba(255, 255, 255, 0.64);
        }

        .message-head,
        .message-route {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            align-items: center;
        }

        .message-card h4 {
            margin: 0;
            font-size: 1.05rem;
        }

        .message-card p {
            margin: 0;
            color: var(--ink-soft);
            line-height: 1.55;
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

        .pill.type {
            color: var(--ok);
            background: rgba(47, 107, 79, 0.12);
        }

        .metadata-box {
            overflow: auto;
            max-height: 8rem;
            padding: 0.8rem;
            border-radius: 0.85rem;
            color: #233047;
            background: rgba(20, 33, 61, 0.06);
            font-family: "Courier New", monospace;
            font-size: 0.82rem;
            line-height: 1.45;
            white-space: pre-wrap;
        }

        @media (max-width: 1050px) {
            .conversation-workspace,
            .conversation-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    <section class="hero">
        <span class="status">Conversation history active</span>
        <h2>Trace agent-to-agent messages.</h2>
        <p>
            Review persisted communication logs across tasks and agent pairs. This is a read-only operational view
            over the existing communication history, with no realtime messaging infrastructure added.
        </p>
    </section>

    <section class="conversation-workspace" aria-label="Agent conversation workspace">
        <aside class="conversation-panel">
            <h3>Filters</h3>
            <p>Filter by a task, one agent, or a specific two-agent conversation pair.</p>

            <form class="filter-form" method="GET" action="{{ route('admin.conversations') }}">
                <div class="field">
                    <label for="task_id">Task</label>
                    <select id="task_id" name="task_id">
                        <option value="">Any task</option>
                        @foreach ($tasks as $task)
                            <option value="{{ $task['id'] }}" @selected(($filters['task_id'] ?? null) === $task['id'])>
                                {{ $task['title'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="agent_id">Single agent</label>
                    <select id="agent_id" name="agent_id">
                        <option value="">Any agent</option>
                        @foreach ($agents as $agent)
                            <option value="{{ $agent['id'] }}" @selected(($filters['agent_id'] ?? null) === $agent['id'])>
                                {{ $agent['name'] }} ({{ $agent['role'] }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="first_agent_id">Pair: first agent</label>
                    <select id="first_agent_id" name="first_agent_id">
                        <option value="">Any agent</option>
                        @foreach ($agents as $agent)
                            <option value="{{ $agent['id'] }}" @selected(($filters['first_agent_id'] ?? null) === $agent['id'])>
                                {{ $agent['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="second_agent_id">Pair: second agent</label>
                    <select id="second_agent_id" name="second_agent_id">
                        <option value="">Any agent</option>
                        @foreach ($agents as $agent)
                            <option value="{{ $agent['id'] }}" @selected(($filters['second_agent_id'] ?? null) === $agent['id'])>
                                {{ $agent['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="button-row">
                    <button class="button" type="submit">Apply filters</button>
                    <a class="button secondary" href="{{ route('admin.conversations') }}">Clear</a>
                </div>
            </form>
        </aside>

        <div class="conversation-panel">
            <div class="conversation-summary" aria-label="Conversation summary">
                <div class="summary-card">
                    <span>Messages</span>
                    <strong>{{ count($messages) }}</strong>
                </div>
                <div class="summary-card">
                    <span>Agents</span>
                    <strong>{{ collect($messages)->flatMap(fn ($message) => [$message['sender_agent_id'], $message['recipient_agent_id']])->filter()->unique()->count() }}</strong>
                </div>
                <div class="summary-card">
                    <span>Tasks</span>
                    <strong>{{ collect($messages)->pluck('task_id')->filter()->unique()->count() }}</strong>
                </div>
            </div>

            <h3>Communication timeline</h3>
            <div class="timeline">
                @forelse ($messages as $message)
                    <article class="message-card">
                        <div class="message-head">
                            <span class="pill type">{{ $message['message_type'] }}</span>
                            <span class="pill">{{ $message['sent_at'] ?: $message['created_at'] }}</span>
                        </div>
                        <div class="message-route">
                            <span class="pill">{{ $message['sender_name'] ?: $message['sender_agent_id'] }}</span>
                            <span>-&gt;</span>
                            <span class="pill">{{ $message['recipient_name'] ?: $message['recipient_agent_id'] }}</span>
                        </div>
                        <h4>{{ $message['subject'] ?: 'Message without subject' }}</h4>
                        <p>{{ $message['body'] }}</p>
                        @if ($message['task_title'])
                            <p><strong>Task:</strong> {{ $message['task_title'] }}</p>
                        @endif
                        @if (count($message['metadata']) > 0)
                            <pre class="metadata-box">{{ json_encode($message['metadata'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        @endif
                    </article>
                @empty
                    <p class="empty-state">No communication logs match the current filters.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection

@section('pageScripts')
    <script>
        (() => {
            window.OfficeConversations = {
                messages: window.OfficeAdmin?.initialMessages || [],
                filters: window.OfficeAdmin?.conversationFilters || {},
                options: window.OfficeAdmin?.conversationOptions || {},
            };
        })();
    </script>
@endsection
