<?php

namespace App\Application\Organizations\Services;

use App\Application\Organizations\Data\OrganizationSettingsData;
use App\Application\Policies\Enums\PolicyRule;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\Role;

final class OrganizationSettingsService
{
    /**
     * @param  array<string, mixed>  $settings
     */
    public function store(Organization|string $organization, array $settings): OrganizationSetting
    {
        return OrganizationSetting::query()->updateOrCreate(
            ['organization_id' => $this->organizationId($organization)],
            [
                'provider_settings' => $settings['provider'] ?? [],
                'memory_settings' => $settings['memory'] ?? [],
                'policy_settings' => $settings['policy'] ?? [],
                'runtime_defaults' => $settings['runtime_defaults'] ?? [],
            ],
        );
    }

    public function resolve(Organization|string $organization): OrganizationSettingsData
    {
        $organizationId = $this->organizationId($organization);
        $persisted = OrganizationSetting::query()
            ->where('organization_id', $organizationId)
            ->first();

        return new OrganizationSettingsData(
            organizationId: $organizationId,
            provider: array_replace_recursive($this->defaultProviderSettings(), $persisted?->provider_settings ?? []),
            memory: array_replace_recursive($this->defaultMemorySettings(), $persisted?->memory_settings ?? []),
            policy: array_replace_recursive($this->defaultPolicySettings(), $persisted?->policy_settings ?? []),
            runtimeDefaults: array_replace_recursive($this->defaultRuntimeSettings(), $persisted?->runtime_defaults ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultProviderSettings(): array
    {
        $embeddingDefault = (string) config('providers.embeddings.default', 'openai_compatible');

        return [
            'llm' => [
                'default' => (string) config('providers.default', 'openai_compatible'),
                'failover' => [
                    'order' => array_values((array) config('providers.failover.order', [])),
                    'fallback_on_retriable_only' => (bool) config('providers.failover.fallback_on_retriable_only', true),
                ],
            ],
            'embeddings' => [
                'default' => $embeddingDefault,
                'model' => data_get(config('providers.embeddings'), $embeddingDefault.'.model'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultMemorySettings(): array
    {
        return [
            'pgvector' => [
                'dimensions' => (int) config('memory.pgvector.dimensions', 1536),
                'distance' => (string) config('memory.pgvector.distance', 'cosine'),
                'require_in_production' => (bool) config('memory.pgvector.require_in_production', false),
            ],
            'retrieval' => [
                'top_k' => (int) config('context.retrieval.top_k', 3),
                'max_distance' => config('context.retrieval.max_distance'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultPolicySettings(): array
    {
        return [
            'approvals_required' => false,
            'tenant_enforcement' => true,
            PolicyRule::AssignmentRequiredAgentCapabilities->value => [],
            PolicyRule::ExecutionRequiredAgentCapabilities->value => [],
            'allowed_admin_roles' => [
                Role::SUPER_ADMIN,
                Role::ADMIN,
                Role::OPERATOR,
                Role::OBSERVER,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultRuntimeSettings(): array
    {
        return [
            'queue' => [
                'execution_connection' => (string) config('queue.runtime.execution_connection', 'redis'),
                'execution_queue' => (string) config('queue.runtime.execution_queue', 'executions'),
                'execution_tries' => (int) config('queue.runtime.execution_tries', 3),
            ],
            'retry' => [
                'max_retries' => (int) config('executions.retry.max_retries', 2),
                'backoff_seconds' => array_values((array) config('executions.retry.backoff_seconds', [60, 300, 900])),
            ],
            'prompts' => [
                'version' => (string) config('prompts.default.version', '2026-04-23.v1'),
                'schema_version' => (string) config('prompts.default.schema_version', '1'),
            ],
        ];
    }

    private function organizationId(Organization|string $organization): string
    {
        return $organization instanceof Organization ? $organization->id : $organization;
    }
}
