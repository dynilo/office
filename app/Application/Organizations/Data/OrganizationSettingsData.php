<?php

namespace App\Application\Organizations\Data;

final readonly class OrganizationSettingsData
{
    /**
     * @param  array<string, mixed>  $provider
     * @param  array<string, mixed>  $memory
     * @param  array<string, mixed>  $policy
     * @param  array<string, mixed>  $runtimeDefaults
     */
    public function __construct(
        public string $organizationId,
        public array $provider,
        public array $memory,
        public array $policy,
        public array $runtimeDefaults,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'provider' => $this->provider,
            'memory' => $this->memory,
            'policy' => $this->policy,
            'runtime_defaults' => $this->runtimeDefaults,
        ];
    }
}
