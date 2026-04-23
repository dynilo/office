@extends('admin.layout')

@section('pageStyles')
    <style>
        .agents-shell {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(21rem, 0.95fr);
            gap: 1.5rem;
            align-items: start;
        }

        .agents-panel {
            border: 1px solid rgba(20, 33, 61, 0.08);
            border-radius: 1.5rem;
            background: rgba(255, 253, 247, 0.78);
            box-shadow: 0 14px 40px rgba(20, 33, 61, 0.06);
        }

        .agents-panel-header {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: end;
            padding: 1.5rem 1.5rem 1rem;
            border-bottom: 1px solid rgba(20, 33, 61, 0.08);
        }

        .agents-panel-header h2,
        .agents-panel-header h3 {
            margin: 0;
            font-size: 1.35rem;
        }

        .agents-panel-header p {
            margin: 0.35rem 0 0;
            color: var(--ink-soft);
            line-height: 1.5;
        }

        .toolbar {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .toolbar input {
            width: 14rem;
            padding: 0.75rem 0.85rem;
            border: 1px solid rgba(20, 33, 61, 0.12);
            border-radius: 0.9rem;
            background: rgba(255,255,255,0.7);
            color: var(--ink);
        }

        .agents-list {
            display: grid;
            gap: 0.85rem;
            padding: 1rem 1.5rem 1.5rem;
        }

        .agent-card {
            display: grid;
            gap: 0.9rem;
            padding: 1rem;
            border: 1px solid rgba(20, 33, 61, 0.08);
            border-radius: 1.2rem;
            background: rgba(255, 255, 255, 0.75);
        }

        .agent-card-header {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: start;
        }

        .agent-card h3 {
            margin: 0;
            font-size: 1.1rem;
        }

        .agent-meta,
        .helper {
            color: var(--ink-soft);
            line-height: 1.5;
        }

        .capsules {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .capsule {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.6rem;
            border-radius: 999px;
            background: rgba(20, 33, 61, 0.08);
            color: var(--ink);
            font-size: 0.82rem;
        }

        .agent-actions,
        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
        }

        .button {
            appearance: none;
            border: 1px solid rgba(20, 33, 61, 0.12);
            border-radius: 0.9rem;
            background: #fffdf7;
            color: var(--ink);
            padding: 0.75rem 1rem;
            font: inherit;
            cursor: pointer;
        }

        .button.primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff8ef;
        }

        .button.subtle {
            background: rgba(20, 33, 61, 0.04);
        }

        .button:disabled {
            opacity: 0.6;
            cursor: wait;
        }

        .agent-form {
            display: grid;
            gap: 1rem;
            padding: 1rem 1.5rem 1.5rem;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .field,
        .field-wide {
            display: grid;
            gap: 0.35rem;
        }

        .field-wide {
            grid-column: 1 / -1;
        }

        .field label,
        .field-wide label {
            font-size: 0.86rem;
            color: var(--ink-soft);
        }

        .field input,
        .field-wide input,
        .field-wide select,
        .field-wide textarea {
            width: 100%;
            padding: 0.8rem 0.9rem;
            border: 1px solid rgba(20, 33, 61, 0.12);
            border-radius: 0.95rem;
            background: rgba(255,255,255,0.72);
            color: var(--ink);
            font: inherit;
        }

        .field-wide textarea {
            min-height: 7.5rem;
            resize: vertical;
        }

        .inline-check {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .inline-check input {
            width: auto;
        }

        .notice,
        .errors {
            padding: 0.9rem 1rem;
            border-radius: 1rem;
            font-size: 0.92rem;
            line-height: 1.55;
        }

        .notice {
            background: rgba(47, 107, 79, 0.12);
            color: var(--ok);
        }

        .errors {
            background: rgba(196, 58, 58, 0.1);
            color: #8f2a2a;
        }

        .empty-state {
            padding: 1.5rem;
            border: 1px dashed rgba(20, 33, 61, 0.18);
            border-radius: 1.2rem;
            color: var(--ink-soft);
            text-align: center;
        }

        @media (max-width: 1100px) {
            .agents-shell {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .agents-panel-header,
            .agent-card-header,
            .toolbar {
                display: grid;
            }

            .toolbar input {
                width: 100%;
            }

            .field-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    <section class="hero">
        <span class="status">Agent management active</span>
        <h2>Agents</h2>
        <p>
            Manage registry records from the admin shell. This page lists agents, creates and edits definitions, and exposes activation controls using the existing agent API.
        </p>
    </section>

    <section class="agents-shell" id="agent-management-app">
        <div class="agents-panel">
            <header class="agents-panel-header">
                <div>
                    <h2>Registry</h2>
                    <p>Operational view of all configured agents.</p>
                </div>

                <div class="toolbar">
                    <input
                        id="agent-search"
                        type="search"
                        placeholder="Filter by name, role, or code"
                        aria-label="Filter agents"
                    >
                    <button class="button subtle" id="agent-refresh" type="button">Refresh</button>
                </div>
            </header>

            <div class="agents-list" id="agent-list">
                @forelse ($agents as $agent)
                    <article class="agent-card">
                        <div class="agent-card-header">
                            <div>
                                <h3>{{ $agent['name'] }}</h3>
                                <p class="agent-meta">{{ $agent['role'] }} · {{ $agent['code'] }}</p>
                            </div>

                            <span class="status">{{ $agent['active'] ? 'Active' : 'Inactive' }}</span>
                        </div>

                        <div class="capsules">
                            @foreach ($agent['capabilities'] as $capability)
                                <span class="capsule">{{ $capability }}</span>
                            @endforeach
                        </div>

                        <p class="helper">Model: {{ $agent['model_preference'] ?? 'Unspecified' }}</p>
                    </article>
                @empty
                    <div class="empty-state">No agents yet. Use the form to create the first one.</div>
                @endforelse
            </div>
        </div>

        <div class="agents-panel">
            <header class="agents-panel-header">
                <div>
                    <h3 id="agent-form-title">Create agent</h3>
                    <p>Use the existing API contract to create or update registry entries.</p>
                </div>
            </header>

            <form class="agent-form" id="agent-form">
                <div class="notice" id="agent-notice" hidden></div>
                <div class="errors" id="agent-errors" hidden></div>

                <div class="field-grid">
                    <div class="field">
                        <label for="agent-name">Name</label>
                        <input id="agent-name" name="name" type="text" required>
                    </div>

                    <div class="field">
                        <label for="agent-code">Code</label>
                        <input id="agent-code" name="code" type="text" required>
                    </div>

                    <div class="field">
                        <label for="agent-role">Role</label>
                        <input id="agent-role" name="role" type="text" required>
                    </div>

                    <div class="field">
                        <label for="agent-model">Model preference</label>
                        <input id="agent-model" name="model_preference" type="text">
                    </div>

                    <div class="field-wide">
                        <label for="agent-capabilities">Capabilities</label>
                        <input id="agent-capabilities" name="capabilities" type="text" placeholder="analysis, reporting, routing">
                    </div>

                    <div class="field">
                        <label for="temp-mode">Temperature mode</label>
                        <select id="temp-mode" name="temperature_mode">
                            <option value="">None</option>
                            <option value="fixed">Fixed</option>
                            <option value="bounded">Bounded</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="temp-value">Temperature value</label>
                        <input id="temp-value" name="temperature_value" type="number" min="0" max="2" step="0.1">
                    </div>

                    <div class="field">
                        <label for="temp-min">Temperature min</label>
                        <input id="temp-min" name="temperature_min" type="number" min="0" max="2" step="0.1">
                    </div>

                    <div class="field">
                        <label for="temp-max">Temperature max</label>
                        <input id="temp-max" name="temperature_max" type="number" min="0" max="2" step="0.1">
                    </div>

                    <div class="field-wide">
                        <label class="inline-check" for="agent-active">
                            <input id="agent-active" name="active" type="checkbox" checked>
                            Set agent active
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="button primary" id="agent-submit" type="submit">Create agent</button>
                    <button class="button subtle" id="agent-reset" type="button">Reset form</button>
                </div>
            </form>
        </div>
    </section>
@endsection

@section('pageScripts')
    <script>
        (() => {
            const bootstrap = window.OfficeAdmin || {};
            const api = bootstrap.agentManagement || {};
            const state = {
                agents: Array.isArray(bootstrap.initialAgents) ? [...bootstrap.initialAgents] : [],
                editingId: null,
                filter: '',
                busy: false,
            };

            const listEl = document.getElementById('agent-list');
            const formEl = document.getElementById('agent-form');
            const titleEl = document.getElementById('agent-form-title');
            const submitEl = document.getElementById('agent-submit');
            const noticeEl = document.getElementById('agent-notice');
            const errorsEl = document.getElementById('agent-errors');
            const searchEl = document.getElementById('agent-search');
            const refreshEl = document.getElementById('agent-refresh');
            const resetEl = document.getElementById('agent-reset');

            const fields = {
                name: document.getElementById('agent-name'),
                code: document.getElementById('agent-code'),
                role: document.getElementById('agent-role'),
                capabilities: document.getElementById('agent-capabilities'),
                modelPreference: document.getElementById('agent-model'),
                temperatureMode: document.getElementById('temp-mode'),
                temperatureValue: document.getElementById('temp-value'),
                temperatureMin: document.getElementById('temp-min'),
                temperatureMax: document.getElementById('temp-max'),
                active: document.getElementById('agent-active'),
            };

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function setNotice(message = '') {
                noticeEl.hidden = message === '';
                noticeEl.textContent = message;
            }

            function setErrors(messages = []) {
                errorsEl.hidden = messages.length === 0;
                errorsEl.innerHTML = messages.map((message) => `<div>${escapeHtml(message)}</div>`).join('');
            }

            function setBusy(nextBusy) {
                state.busy = nextBusy;
                submitEl.disabled = nextBusy;
                refreshEl.disabled = nextBusy;
                searchEl.disabled = nextBusy;
            }

            function visibleAgents() {
                const filter = state.filter.trim().toLowerCase();

                if (filter === '') {
                    return state.agents;
                }

                return state.agents.filter((agent) => {
                    const haystack = [
                        agent.name,
                        agent.code,
                        agent.role,
                        ...(agent.capabilities || []),
                    ].join(' ').toLowerCase();

                    return haystack.includes(filter);
                });
            }

            function temperatureSummary(policy) {
                if (!policy || !policy.mode) {
                    return 'No temperature policy';
                }

                if (policy.mode === 'fixed') {
                    return `Fixed · ${policy.value ?? 'n/a'}`;
                }

                return `Bounded · ${policy.min ?? 'n/a'} to ${policy.max ?? 'n/a'}`;
            }

            function renderAgents() {
                const agents = visibleAgents();

                if (agents.length === 0) {
                    listEl.innerHTML = '<div class="empty-state">No matching agents for the current view.</div>';
                    return;
                }

                listEl.innerHTML = agents.map((agent) => {
                    const activeLabel = agent.active ? 'Deactivate' : 'Activate';

                    return `
                        <article class="agent-card" data-agent-id="${escapeHtml(agent.id)}">
                            <div class="agent-card-header">
                                <div>
                                    <h3>${escapeHtml(agent.name)}</h3>
                                    <p class="agent-meta">${escapeHtml(agent.role)} · ${escapeHtml(agent.code)}</p>
                                </div>
                                <span class="status">${agent.active ? 'Active' : 'Inactive'}</span>
                            </div>
                            <div class="capsules">
                                ${(agent.capabilities || []).map((capability) => `<span class="capsule">${escapeHtml(capability)}</span>`).join('')}
                            </div>
                            <p class="helper">Model: ${escapeHtml(agent.model_preference || 'Unspecified')}</p>
                            <p class="helper">${escapeHtml(temperatureSummary(agent.temperature_policy))}</p>
                            <div class="agent-actions">
                                <button class="button subtle" type="button" data-action="edit" data-id="${escapeHtml(agent.id)}">Edit</button>
                                <button class="button" type="button" data-action="toggle" data-id="${escapeHtml(agent.id)}">${activeLabel}</button>
                            </div>
                        </article>
                    `;
                }).join('');
            }

            function resetForm(message = '') {
                state.editingId = null;
                formEl.reset();
                fields.active.checked = true;
                titleEl.textContent = 'Create agent';
                submitEl.textContent = 'Create agent';
                setErrors([]);
                setNotice(message);
            }

            function fillForm(agent) {
                state.editingId = agent.id;
                fields.name.value = agent.name || '';
                fields.code.value = agent.code || '';
                fields.role.value = agent.role || '';
                fields.capabilities.value = (agent.capabilities || []).join(', ');
                fields.modelPreference.value = agent.model_preference || '';
                fields.temperatureMode.value = agent.temperature_policy?.mode || '';
                fields.temperatureValue.value = agent.temperature_policy?.value ?? '';
                fields.temperatureMin.value = agent.temperature_policy?.min ?? '';
                fields.temperatureMax.value = agent.temperature_policy?.max ?? '';
                fields.active.checked = !!agent.active;
                titleEl.textContent = `Edit ${agent.name}`;
                submitEl.textContent = 'Save changes';
                setErrors([]);
                setNotice(`Editing ${agent.name}.`);
            }

            async function fetchJson(url, options = {}) {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        ...(options.body ? {'Content-Type': 'application/json'} : {}),
                        ...(options.headers || {}),
                    },
                    credentials: 'same-origin',
                    ...options,
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw payload;
                }

                return payload;
            }

            async function refreshAgents(message = '') {
                setBusy(true);
                setErrors([]);

                try {
                    const payload = await fetchJson(`${api.list}?sort=name&direction=asc&per_page=100`);
                    state.agents = Array.isArray(payload.data) ? payload.data : [];
                    renderAgents();
                    setNotice(message);
                } catch (error) {
                    setErrors(['Unable to load agents from the API.']);
                } finally {
                    setBusy(false);
                }
            }

            function payloadFromForm() {
                const capabilities = fields.capabilities.value
                    .split(',')
                    .map((value) => value.trim())
                    .filter((value, index, values) => value !== '' && values.indexOf(value) === index);

                const mode = fields.temperatureMode.value.trim();
                let temperaturePolicy = null;

                if (mode !== '') {
                    temperaturePolicy = {
                        mode,
                    };

                    if (fields.temperatureValue.value !== '') {
                        temperaturePolicy.value = Number(fields.temperatureValue.value);
                    }

                    if (fields.temperatureMin.value !== '') {
                        temperaturePolicy.min = Number(fields.temperatureMin.value);
                    }

                    if (fields.temperatureMax.value !== '') {
                        temperaturePolicy.max = Number(fields.temperatureMax.value);
                    }
                }

                return {
                    name: fields.name.value.trim(),
                    code: fields.code.value.trim(),
                    role: fields.role.value.trim(),
                    capabilities,
                    model_preference: fields.modelPreference.value.trim() || null,
                    temperature_policy: temperaturePolicy,
                    active: fields.active.checked,
                };
            }

            function flattenErrors(error) {
                if (error?.errors && typeof error.errors === 'object') {
                    return Object.values(error.errors).flat().map((value) => String(value));
                }

                if (typeof error?.message === 'string') {
                    return [error.message];
                }

                return ['The request could not be completed.'];
            }

            formEl.addEventListener('submit', async (event) => {
                event.preventDefault();
                setBusy(true);
                setErrors([]);
                setNotice('');

                const payload = payloadFromForm();
                const isEditing = state.editingId !== null;
                const url = isEditing ? `${api.update}/${state.editingId}` : api.create;
                const method = isEditing ? 'PATCH' : 'POST';

                try {
                    const response = await fetchJson(url, {
                        method,
                        body: JSON.stringify(payload),
                    });

                    const savedAgent = response.data;
                    const index = state.agents.findIndex((agent) => agent.id === savedAgent.id);

                    if (index >= 0) {
                        state.agents.splice(index, 1, savedAgent);
                    } else {
                        state.agents.push(savedAgent);
                    }

                    state.agents.sort((left, right) => left.name.localeCompare(right.name) || left.id.localeCompare(right.id));
                    renderAgents();
                    resetForm(isEditing ? `${savedAgent.name} updated.` : `${savedAgent.name} created.`);
                } catch (error) {
                    setErrors(flattenErrors(error));
                } finally {
                    setBusy(false);
                }
            });

            listEl.addEventListener('click', async (event) => {
                const target = event.target.closest('[data-action]');

                if (!target || state.busy) {
                    return;
                }

                const id = target.getAttribute('data-id');
                const action = target.getAttribute('data-action');

                if (!id || !action) {
                    return;
                }

                if (action === 'edit') {
                    setBusy(true);
                    setErrors([]);

                    try {
                        const response = await fetchJson(`${api.show}/${id}`);
                        fillForm(response.data);
                    } catch (error) {
                        setErrors(flattenErrors(error));
                    } finally {
                        setBusy(false);
                    }

                    return;
                }

                if (action === 'toggle') {
                    const agent = state.agents.find((candidate) => candidate.id === id);

                    if (!agent) {
                        return;
                    }

                    setBusy(true);
                    setErrors([]);

                    try {
                        const endpoint = agent.active ? `${api.deactivate}/${id}/deactivate` : `${api.activate}/${id}/activate`;
                        const response = await fetchJson(endpoint, {
                            method: 'PATCH',
                        });

                        const index = state.agents.findIndex((candidate) => candidate.id === id);
                        if (index >= 0) {
                            state.agents.splice(index, 1, response.data);
                        }

                        renderAgents();
                        setNotice(`${response.data.name} ${response.data.active ? 'activated' : 'deactivated'}.`);

                        if (state.editingId === id) {
                            fillForm(response.data);
                        }
                    } catch (error) {
                        setErrors(flattenErrors(error));
                    } finally {
                        setBusy(false);
                    }
                }
            });

            searchEl.addEventListener('input', (event) => {
                state.filter = event.target.value || '';
                renderAgents();
            });

            refreshEl.addEventListener('click', () => {
                void refreshAgents('Registry refreshed.');
            });

            resetEl.addEventListener('click', () => {
                resetForm();
            });

            renderAgents();
        })();
    </script>
@endsection
