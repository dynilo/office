<?php

use App\Application\Organizations\Services\OrganizationSettingsService;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates the organization settings table and relation', function (): void {
    $organization = Organization::factory()->create();
    $setting = OrganizationSetting::factory()->for($organization)->create();

    expect(Schema::hasTable('organization_settings'))->toBeTrue()
        ->and(Schema::hasColumns('organization_settings', [
            'organization_id',
            'provider_settings',
            'memory_settings',
            'policy_settings',
            'runtime_defaults',
        ]))->toBeTrue()
        ->and($organization->settings?->is($setting))->toBeTrue();
});

it('resolves default settings when an organization has no overrides', function (): void {
    config()->set('providers.default', 'failover');
    config()->set('providers.failover.order', ['openai_compatible', 'openai_compatible_secondary']);
    config()->set('providers.failover.fallback_on_retriable_only', true);
    config()->set('providers.embeddings.default', 'openai_compatible');
    config()->set('providers.embeddings.openai_compatible.model', 'text-embedding-3-small');
    config()->set('memory.pgvector.dimensions', 3072);
    config()->set('memory.pgvector.distance', 'inner_product');
    config()->set('context.retrieval.top_k', 7);
    config()->set('executions.retry.max_retries', 4);
    config()->set('queue.runtime.execution_queue', 'org-executions');
    config()->set('prompts.default.version', 'org-default-v2');

    $settings = app(OrganizationSettingsService::class)->resolve(Organization::factory()->create());

    expect($settings->provider['llm']['default'])->toBe('failover')
        ->and($settings->provider['llm']['failover']['order'])->toBe(['openai_compatible', 'openai_compatible_secondary'])
        ->and($settings->provider['embeddings']['model'])->toBe('text-embedding-3-small')
        ->and($settings->memory['pgvector']['dimensions'])->toBe(3072)
        ->and($settings->memory['retrieval']['top_k'])->toBe(7)
        ->and($settings->policy['tenant_enforcement'])->toBeTrue()
        ->and($settings->runtimeDefaults['queue']['execution_queue'])->toBe('org-executions')
        ->and($settings->runtimeDefaults['retry']['max_retries'])->toBe(4)
        ->and($settings->runtimeDefaults['prompts']['version'])->toBe('org-default-v2');
});

it('stores and resolves per organization overrides without losing defaults', function (): void {
    config()->set('providers.default', 'openai_compatible');
    config()->set('providers.failover.order', ['openai_compatible']);
    config()->set('context.retrieval.top_k', 3);
    config()->set('queue.runtime.execution_connection', 'redis');
    config()->set('queue.runtime.execution_tries', 3);

    $organization = Organization::factory()->create();
    $service = app(OrganizationSettingsService::class);

    $service->store($organization, [
        'provider' => [
            'llm' => [
                'default' => 'openai_compatible_secondary',
            ],
        ],
        'memory' => [
            'retrieval' => [
                'top_k' => 12,
            ],
        ],
        'policy' => [
            'approvals_required' => true,
        ],
        'runtime_defaults' => [
            'queue' => [
                'execution_queue' => 'tenant-priority',
            ],
        ],
    ]);

    $resolved = $service->resolve($organization);

    expect($resolved->provider['llm']['default'])->toBe('openai_compatible_secondary')
        ->and($resolved->provider['llm']['failover']['order'])->toBe(['openai_compatible'])
        ->and($resolved->memory['retrieval']['top_k'])->toBe(12)
        ->and($resolved->policy['approvals_required'])->toBeTrue()
        ->and($resolved->policy['allowed_admin_roles'])->toContain('super_admin')
        ->and($resolved->runtimeDefaults['queue']['execution_connection'])->toBe('redis')
        ->and($resolved->runtimeDefaults['queue']['execution_queue'])->toBe('tenant-priority')
        ->and($organization->fresh()->settings)->not->toBeNull();
});

it('keeps organization settings isolated per organization', function (): void {
    $service = app(OrganizationSettingsService::class);
    $alpha = Organization::factory()->create(['slug' => 'alpha-settings']);
    $beta = Organization::factory()->create(['slug' => 'beta-settings']);

    $service->store($alpha, [
        'provider' => [
            'llm' => [
                'default' => 'openai_compatible_secondary',
            ],
        ],
        'policy' => [
            'approvals_required' => true,
        ],
    ]);

    $service->store($beta, [
        'memory' => [
            'retrieval' => [
                'top_k' => 9,
            ],
        ],
        'runtime_defaults' => [
            'prompts' => [
                'version' => 'beta-v1',
            ],
        ],
    ]);

    $alphaResolved = $service->resolve($alpha);
    $betaResolved = $service->resolve($beta);

    expect($alphaResolved->provider['llm']['default'])->toBe('openai_compatible_secondary')
        ->and($alphaResolved->policy['approvals_required'])->toBeTrue()
        ->and($betaResolved->provider['llm']['default'])->not->toBe('openai_compatible_secondary')
        ->and($betaResolved->memory['retrieval']['top_k'])->toBe(9)
        ->and($betaResolved->runtimeDefaults['prompts']['version'])->toBe('beta-v1');
});
