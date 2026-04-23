<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\ProviderUsageRecord;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderUsageRecord>
 */
class ProviderUsageRecordFactory extends Factory
{
    protected $model = ProviderUsageRecord::class;

    public function definition(): array
    {
        $inputTokens = fake()->numberBetween(100, 10_000);
        $outputTokens = fake()->numberBetween(50, 5_000);

        return [
            'execution_id' => Execution::factory(),
            'task_id' => Task::factory(),
            'agent_id' => Agent::factory(),
            'provider' => 'openai_compatible',
            'model' => 'gpt-5',
            'response_id' => fake()->uuid(),
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'total_tokens' => $inputTokens + $outputTokens,
            'estimated_cost_micros' => fake()->numberBetween(1, 100_000),
            'currency' => 'USD',
            'pricing_source' => 'openai_compatible:*',
            'metadata' => [
                'request_id' => fake()->uuid(),
            ],
        ];
    }
}
