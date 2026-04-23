<?php

namespace App\Application\Policies\Data;

use App\Application\Policies\Enums\PolicyRule;

final readonly class PolicyDecisionData
{
    /**
     * @param  array<int, string>  $requiredCapabilities
     * @param  array<int, string>  $missingCapabilities
     */
    public function __construct(
        public bool $allowed,
        public PolicyRule $rule,
        public array $requiredCapabilities = [],
        public array $missingCapabilities = [],
    ) {}

    public static function allow(PolicyRule $rule, array $requiredCapabilities = []): self
    {
        return new self(
            allowed: true,
            rule: $rule,
            requiredCapabilities: $requiredCapabilities,
        );
    }

    public static function deny(PolicyRule $rule, array $requiredCapabilities, array $missingCapabilities): self
    {
        return new self(
            allowed: false,
            rule: $rule,
            requiredCapabilities: $requiredCapabilities,
            missingCapabilities: $missingCapabilities,
        );
    }

    public function reasonCode(): string
    {
        return 'policy_'.$this->rule->value;
    }

    public function message(): string
    {
        return match ($this->rule) {
            PolicyRule::AssignmentRequiredAgentCapabilities => sprintf(
                'Agent is blocked by assignment policy. Missing required policy capabilities: %s.',
                implode(', ', $this->missingCapabilities),
            ),
            PolicyRule::ExecutionRequiredAgentCapabilities => sprintf(
                'Agent is blocked by execution policy. Missing required policy capabilities: %s.',
                implode(', ', $this->missingCapabilities),
            ),
        };
    }
}
