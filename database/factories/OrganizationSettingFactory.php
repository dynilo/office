<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationSetting>
 */
class OrganizationSettingFactory extends Factory
{
    protected $model = OrganizationSetting::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'provider_settings' => [
                'llm' => [
                    'default' => 'openai_compatible',
                ],
            ],
            'memory_settings' => [
                'retrieval' => [
                    'top_k' => 4,
                ],
            ],
            'policy_settings' => [
                'approvals_required' => false,
            ],
            'runtime_defaults' => [
                'queue' => [
                    'execution_queue' => 'executions',
                ],
            ],
        ];
    }
}
