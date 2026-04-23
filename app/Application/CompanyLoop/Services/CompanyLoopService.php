<?php

namespace App\Application\CompanyLoop\Services;

use App\Application\Agents\Services\CoordinatorAgent;
use App\Application\Agents\Services\Specialists\AbstractSpecialistAgent;
use App\Application\Agents\Services\Specialists\FinanceAnalystAgent;
use App\Application\Agents\Services\Specialists\LegalComplianceReviewAgent;
use App\Application\Agents\Services\Specialists\StrategyAnalystAgent;
use App\Application\Artifacts\Services\ArtifactPersistenceService;
use App\Application\Communications\Data\AgentMessageData;
use App\Application\Communications\Services\AgentCommunicationWriter;
use App\Application\CompanyLoop\Data\CompanyLoopReportData;
use App\Application\Executions\Services\ExecutionLifecycleService;
use App\Application\Tasks\Data\TaskDecompositionChildData;
use App\Application\Tasks\Services\AssignTaskService;
use App\Application\Tasks\Services\TaskDecompositionService;
use App\Application\Tasks\Services\TaskLifecycleService;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Artifact;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Support\Exceptions\InvalidStateException;

final class CompanyLoopService
{
    /**
     * @var array<string, class-string<AbstractSpecialistAgent>>
     */
    private const SPECIALISTS = [
        'strategy' => StrategyAnalystAgent::class,
        'finance' => FinanceAnalystAgent::class,
        'legal_compliance' => LegalComplianceReviewAgent::class,
    ];

    public function __construct(
        private readonly CoordinatorAgent $coordinatorAgent,
        private readonly TaskDecompositionService $decomposition,
        private readonly AssignTaskService $assignment,
        private readonly TaskLifecycleService $tasks,
        private readonly ExecutionLifecycleService $executions,
        private readonly ArtifactPersistenceService $artifacts,
        private readonly AgentCommunicationWriter $communications,
    ) {
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function run(Agent $coordinator, string $goal, array $context = []): CompanyLoopReportData
    {
        $coordinatorReport = $this->coordinatorAgent->coordinate($coordinator, $goal, $context);
        $parent = Task::query()->findOrFail($coordinatorReport->taskId);

        $decomposition = $this->decomposition->decompose($parent, $this->childIntents($goal, $context));
        $childReports = [];

        foreach ($decomposition->children as $child) {
            $childReports[] = $this->runChildTask($coordinator, $child->fresh() ?? $child);
        }

        $report = new CompanyLoopReportData(
            goal: $goal,
            status: 'completed',
            parentTaskId: $parent->id,
            childTaskCount: count($childReports),
            childReports: $childReports,
            summary: $this->summarize($goal, $childReports),
        );

        $artifact = $this->artifacts->store(
            task: $parent->fresh() ?? $parent,
            execution: null,
            kind: 'json',
            name: 'coordinator_final_report',
            contentJson: $report->toArray(),
            metadata: [
                'source' => 'company_loop',
                'child_task_count' => count($childReports),
            ],
        );

        return new CompanyLoopReportData(
            goal: $report->goal,
            status: $report->status,
            parentTaskId: $report->parentTaskId,
            childTaskCount: $report->childTaskCount,
            childReports: $report->childReports,
            summary: $report->summary,
            finalReportArtifactId: $artifact->id,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, TaskDecompositionChildData>
     */
    private function childIntents(string $goal, array $context): array
    {
        return [
            new TaskDecompositionChildData(
                title: 'Strategy assessment: '.$goal,
                summary: 'Assess strategic options and risks for the goal.',
                description: 'Produce an executive strategy brief for: '.$goal,
                payload: [
                    'goal' => $goal,
                    'context' => $context,
                    'required_capabilities' => ['analysis'],
                ],
                requestedAgentRole: 'strategy',
            ),
            new TaskDecompositionChildData(
                title: 'Finance assessment: '.$goal,
                summary: 'Assess financial assumptions, metrics, and risks.',
                description: 'Produce a finance analysis for: '.$goal,
                payload: [
                    'goal' => $goal,
                    'context' => $context,
                    'required_capabilities' => ['analysis'],
                ],
                requestedAgentRole: 'finance',
            ),
            new TaskDecompositionChildData(
                title: 'Compliance assessment: '.$goal,
                summary: 'Assess compliance flags and required follow-up.',
                description: 'Produce a legal and compliance review for: '.$goal,
                payload: [
                    'goal' => $goal,
                    'context' => $context,
                    'required_capabilities' => ['analysis'],
                ],
                requestedAgentRole: 'legal_compliance',
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function runChildTask(Agent $coordinator, Task $child): array
    {
        $queued = $this->tasks->transition($child, TaskStatus::Queued);
        $decision = $this->assignment->assign($queued->id);

        if ($decision->agentId === null) {
            throw new InvalidStateException('Company loop child task could not be assigned.');
        }

        $task = Task::query()->with('agent.profile')->findOrFail($queued->id);
        $agent = $task->agent;

        if (! $agent instanceof Agent) {
            throw new InvalidStateException('Company loop child task assignment could not be loaded.');
        }

        $this->communications->write(new AgentMessageData(
            senderAgentId: $coordinator->id,
            recipientAgentId: $agent->id,
            messageType: 'company_loop.handoff',
            body: 'Coordinator assigned a specialist workstream for goal: '.($task->payload['goal'] ?? $task->title),
            subject: $task->title,
            taskId: $task->id,
            metadata: [
                'parent_task_id' => $task->parent_task_id,
                'requested_agent_role' => $task->requested_agent_role,
            ],
        ));

        $execution = $this->executions->createPendingForAssignedTask(
            taskId: $task->id,
            idempotencyKey: 'company-loop-task-'.$task->id.'-attempt-1',
        );

        $task = $this->tasks->transition($task->fresh(['executions']) ?? $task, TaskStatus::InProgress);
        $running = $this->executions->markRunning($execution->id);

        $result = $this->resolveSpecialist($agent)->run($task->fresh() ?? $task, $agent);
        $succeeded = $this->executions->markSucceeded(
            executionId: $running->id,
            outputPayload: $result['structured_result'],
            providerResponse: $result['raw_response'],
        );

        $this->persistOutputs($task->fresh() ?? $task, $succeeded, $result);
        $completed = $this->tasks->transition($task->fresh(['executions']) ?? $task, TaskStatus::Completed);

        $this->communications->write(new AgentMessageData(
            senderAgentId: $agent->id,
            recipientAgentId: $coordinator->id,
            messageType: 'company_loop.result',
            body: 'Specialist completed workstream '.$completed->title,
            subject: $completed->title,
            taskId: $completed->id,
            metadata: [
                'execution_id' => $succeeded->id,
                'output_type' => $result['structured_result']['output_type'] ?? null,
            ],
        ));

        return [
            'task_id' => $completed->id,
            'execution_id' => $succeeded->id,
            'agent_id' => $agent->id,
            'agent_role' => $agent->role,
            'status' => $completed->status?->value,
            'output_type' => $result['structured_result']['output_type'] ?? null,
            'structured_result' => $result['structured_result'],
        ];
    }

    private function resolveSpecialist(Agent $agent): AbstractSpecialistAgent
    {
        if ($agent->status !== AgentStatus::Active) {
            throw new InvalidStateException('Company loop specialist agent must be active.');
        }

        $serviceClass = self::SPECIALISTS[$agent->role] ?? null;

        if ($serviceClass === null) {
            throw new InvalidStateException('No specialist agent service exists for role ['.$agent->role.'].');
        }

        /** @var AbstractSpecialistAgent $specialist */
        $specialist = app($serviceClass);

        return $specialist;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function persistOutputs(Task $task, Execution $execution, array $result): void
    {
        $this->artifacts->store(
            task: $task,
            execution: $execution,
            kind: 'json',
            name: 'structured_result',
            contentJson: $result['structured_result'],
            metadata: [
                'source' => 'company_loop',
                'agent_role' => $result['structured_result']['role'] ?? null,
            ],
        );

        $this->artifacts->store(
            task: $task,
            execution: $execution,
            kind: 'text',
            name: 'raw_response',
            contentText: (string) ($result['raw_response']['content'] ?? ''),
            contentJson: $result['raw_response'],
            metadata: [
                'source' => 'company_loop',
            ],
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $childReports
     */
    private function summarize(string $goal, array $childReports): string
    {
        $roles = implode(', ', array_map(
            static fn (array $report): string => (string) $report['agent_role'],
            $childReports,
        ));

        return sprintf(
            'Company loop completed goal [%s] with specialist outputs from: %s.',
            $goal,
            $roles,
        );
    }
}
