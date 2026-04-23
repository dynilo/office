<?php

namespace App\Application\Policies\Enums;

enum PolicyRule: string
{
    case AssignmentRequiredAgentCapabilities = 'assignment_required_agent_capabilities';
    case ExecutionRequiredAgentCapabilities = 'execution_required_agent_capabilities';
}
