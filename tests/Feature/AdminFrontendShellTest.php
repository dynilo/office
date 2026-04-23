<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the admin dashboard shell', function (): void {
    $response = $this->get('/admin');

    $response->assertOk()
        ->assertSee('Admin Shell')
        ->assertSee('Dashboard')
        ->assertSee('window.OfficeAdmin', false)
        ->assertSee('/api/admin/summary');
});

it('renders scaffolded admin pages with stable navigation', function (): void {
    $pages = [
        '/admin/agents' => 'Agents',
        '/admin/tasks' => 'Tasks',
        '/admin/executions' => 'Executions',
        '/admin/audit' => 'Audit',
    ];

    foreach ($pages as $uri => $title) {
        $this->get($uri)
            ->assertOk()
            ->assertSee('Admin Shell')
            ->assertSee($title)
            ->assertSee('\/api\/admin\/summary', false);
    }
});
