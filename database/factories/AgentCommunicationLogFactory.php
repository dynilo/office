<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentCommunicationLog;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentCommunicationLog>
 */
class AgentCommunicationLogFactory extends Factory
{
    protected $model = AgentCommunicationLog::class;

    public function definition(): array
    {
        return [
            'sender_agent_id' => Agent::factory(),
            'recipient_agent_id' => Agent::factory(),
            'task_id' => Task::factory(),
            'message_type' => fake()->randomElement(['coordination.note', 'handoff.request', 'status.update']),
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'metadata' => [
                'source' => 'factory',
            ],
            'sent_at' => now(),
        ];
    }
}
