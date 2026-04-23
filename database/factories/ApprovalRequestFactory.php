<?php

namespace Database\Factories;

use App\Application\Approvals\Enums\ApprovalAction;
use App\Application\Approvals\Enums\ApprovalStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\ApprovalRequest;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalRequest>
 */
class ApprovalRequestFactory extends Factory
{
    protected $model = ApprovalRequest::class;

    public function definition(): array
    {
        return [
            'organization_id' => null,
            'task_id' => Task::factory(),
            'agent_id' => Agent::factory(),
            'action' => ApprovalAction::ExecutionStart->value,
            'status' => ApprovalStatus::Pending,
            'reason' => fake()->sentence(),
            'metadata' => [
                'source' => 'factory',
            ],
            'requested_at' => now()->subMinute(),
            'decided_at' => null,
            'decided_by_type' => null,
            'decided_by_id' => null,
        ];
    }
}
