<?php

namespace App\Application\Policies\Services;

use App\Application\Organizations\Services\OrganizationSettingsService;
use App\Application\Policies\Data\PolicyDecisionData;
use App\Application\Policies\Enums\PolicyRule;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Task;

final class PolicyEngineService
{
    public function __construct(
        private readonly OrganizationSettingsService $organizationSettings,
    ) {}

    public function authorizeAssignment(Task $task, Agent $agent): PolicyDecisionData
    {
        $requiredCapabilities = $this->policyCapabilities(
            task: $task,
            rule: PolicyRule::AssignmentRequiredAgentCapabilities,
        );
        $missing = $this->missingCapabilities($agent, $requiredCapabilities);

        if ($missing !== []) {
            return PolicyDecisionData::deny(
                rule: PolicyRule::AssignmentRequiredAgentCapabilities,
                requiredCapabilities: $requiredCapabilities,
                missingCapabilities: $missing,
            );
        }

        return PolicyDecisionData::allow(
            rule: PolicyRule::AssignmentRequiredAgentCapabilities,
            requiredCapabilities: $requiredCapabilities,
        );
    }

    public function authorizeExecution(Task $task, Agent $agent): PolicyDecisionData
    {
        $requiredCapabilities = $this->policyCapabilities(
            task: $task,
            rule: PolicyRule::ExecutionRequiredAgentCapabilities,
        );
        $missing = $this->missingCapabilities($agent, $requiredCapabilities);

        if ($missing !== []) {
            return PolicyDecisionData::deny(
                rule: PolicyRule::ExecutionRequiredAgentCapabilities,
                requiredCapabilities: $requiredCapabilities,
                missingCapabilities: $missing,
            );
        }

        return PolicyDecisionData::allow(
            rule: PolicyRule::ExecutionRequiredAgentCapabilities,
            requiredCapabilities: $requiredCapabilities,
        );
    }

    /**
     * @return array<int, string>
     */
    private function policyCapabilities(Task $task, PolicyRule $rule): array
    {
        if (! is_string($task->organization_id) || $task->organization_id === '') {
            return [];
        }

        $settings = $this->organizationSettings->resolve($task->organization_id);

        return collect($settings->policy[$rule->value] ?? [])
            ->filter(fn (mixed $capability): bool => is_string($capability) && $capability !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $requiredCapabilities
     * @return array<int, string>
     */
    private function missingCapabilities(Agent $agent, array $requiredCapabilities): array
    {
        if ($requiredCapabilities === []) {
            return [];
        }

        $agentCapabilities = collect($agent->capabilities ?? []);

        return collect($requiredCapabilities)
            ->reject(fn (string $capability): bool => $agentCapabilities->contains($capability))
            ->values()
            ->all();
    }
}
