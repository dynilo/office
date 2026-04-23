<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
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
        return $this->page('agents', 'Agents');
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

    private function page(string $page, string $title): View
    {
        return view('admin.page', [
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
            ],
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
}
