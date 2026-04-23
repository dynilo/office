<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class AdminShellController extends Controller
{
    public function dashboard(): View
    {
        return $this->page('dashboard', 'Dashboard');
    }

    public function agents(): View
    {
        $agents = Agent::query()
            ->with('profile')
            ->orderBy('name')
            ->get()
            ->map(fn (Agent $agent): array => $this->serializeAgent($agent))
            ->values()
            ->all();

        return $this->page(
            page: 'agents',
            title: 'Agents',
            view: 'admin.agents',
            bootstrap: [
                'initialAgents' => $agents,
                'agentManagement' => [
                    'list' => route('api.admin.agents'),
                    'create' => url('/api/agents'),
                    'show' => url('/api/agents'),
                    'update' => url('/api/agents'),
                    'activate' => url('/api/agents'),
                    'deactivate' => url('/api/agents'),
                ],
            ],
            data: [
                'agents' => $agents,
            ],
        );
    }

    public function tasks(): View
    {
        return $this->page('tasks', 'Tasks');
    }

    public function executions(): View
    {
        return $this->page('executions', 'Executions');
    }

    public function audit(): View
    {
        return $this->page('audit', 'Audit');
    }

    private function page(
        string $page,
        string $title,
        string $view = 'admin.page',
        array $bootstrap = [],
        array $data = [],
    ): View
    {
        return view($view, [
            'page' => $page,
            'pageTitle' => $title,
            'navigation' => $this->navigation(),
            'bootstrap' => [
                'app' => config('app.name'),
                'page' => $page,
                'title' => $title,
                'api' => [
                    'summary' => route('api.admin.summary'),
                    'agents' => route('api.admin.agents'),
                    'tasks' => route('api.admin.tasks'),
                    'executions' => route('api.admin.executions'),
                    'auditEvents' => route('api.admin.audit-events'),
                ],
                'navigation' => $this->navigation()->values()->all(),
                ...$bootstrap,
            ],
            ...$data,
        ]);
    }

    /**
     * @return Collection<int, array{key: string, label: string, href: string}>
     */
    private function navigation(): Collection
    {
        return collect([
            ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => route('admin.dashboard')],
            ['key' => 'agents', 'label' => 'Agents', 'href' => route('admin.agents')],
            ['key' => 'tasks', 'label' => 'Tasks', 'href' => route('admin.tasks')],
            ['key' => 'executions', 'label' => 'Executions', 'href' => route('admin.executions')],
            ['key' => 'audit', 'label' => 'Audit', 'href' => route('admin.audit')],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAgent(Agent $agent): array
    {
        return [
            'id' => $agent->id,
            'name' => $agent->name,
            'code' => $agent->code,
            'role' => $agent->role,
            'capabilities' => $agent->capabilities ?? [],
            'model_preference' => $agent->profile?->model_preference,
            'temperature_policy' => $agent->profile?->temperature_policy,
            'active' => $agent->status?->isOperational() ?? false,
            'status' => $agent->status?->value,
            'profile_id' => $agent->profile?->id,
            'created_at' => $agent->created_at?->toIso8601String(),
            'updated_at' => $agent->updated_at?->toIso8601String(),
        ];
    }
}
