<?php

namespace App\Support\CompanyLoop;

use App\Application\Agents\Services\CoordinatorAgent;
use App\Application\CompanyLoop\Services\CompanyLoopService;
use App\Application\Providers\Contracts\LlmProvider;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Executions\Enums\ExecutionStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentCommunicationLog;
use App\Infrastructure\Persistence\Eloquent\Models\Artifact;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Support\Security\SecretRedactor;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class CompanyLoopProductionAcceptance
{
    /**
     * @var array<int, string>
     */
    private const REQUIRED_SPECIALIST_ROLES = [
        'strategy',
        'finance',
        'legal_compliance',
    ];

    public function __construct(
        private CompanyLoopService $companyLoop,
        private SecretRedactor $redactor,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $coordinator = Agent::query()
            ->with('profile')
            ->where('role', CoordinatorAgent::ROLE)
            ->where('status', AgentStatus::Active->value)
            ->orderBy('code')
            ->orderBy('id')
            ->first();

        $specialists = Agent::query()
            ->with('profile')
            ->where('status', AgentStatus::Active->value)
            ->whereIn('role', self::REQUIRED_SPECIALIST_ROLES)
            ->orderBy('code')
            ->orderBy('id')
            ->get()
            ->groupBy('role');

        $checks = [
            'coordinator_active_available' => $coordinator instanceof Agent,
            'required_specialists_available' => collect(self::REQUIRED_SPECIALIST_ROLES)
                ->every(fn (string $role): bool => $specialists->has($role) && $specialists[$role]->isNotEmpty()),
            'coordinator_profile_configured' => $coordinator?->profile !== null && filled($coordinator->profile->model_preference),
            'specialist_profiles_configured' => collect(self::REQUIRED_SPECIALIST_ROLES)
                ->every(fn (string $role): bool => $specialists->get($role, collect())
                    ->contains(fn (Agent $agent): bool => $agent->profile !== null && filled($agent->profile->model_preference))),
            'specialist_capabilities_present' => collect(self::REQUIRED_SPECIALIST_ROLES)
                ->every(fn (string $role): bool => $specialists->get($role, collect())
                    ->contains(fn (Agent $agent): bool => in_array('analysis', $agent->capabilities ?? [], true))),
            'prompt_version_explicit' => filled(config('prompts.default.version')),
            'llm_provider_resolvable' => false,
            'probe_completed' => false,
            'probe_child_tasks_completed' => false,
            'probe_executions_succeeded' => false,
            'probe_artifacts_persisted' => false,
            'probe_communications_persisted' => false,
            'probe_final_report_persisted' => false,
            'probe_rolls_back_cleanly' => false,
        ];

        $runtime = [
            'coordinator_id' => $coordinator?->id,
            'specialist_agent_ids' => collect(self::REQUIRED_SPECIALIST_ROLES)
                ->mapWithKeys(fn (string $role): array => [$role => $specialists->get($role, collect())->pluck('id')->values()->all()])
                ->all(),
            'prompt_version' => config('prompts.default.version'),
            'llm_provider_class' => null,
            'probe' => [
                'goal' => 'Production acceptance validation for the company loop.',
                'parent_task_id' => null,
                'child_task_ids' => [],
                'execution_ids' => [],
                'artifact_names' => [],
                'communication_types' => [],
                'summary' => null,
                'error' => null,
            ],
        ];

        try {
            $runtime['llm_provider_class'] = app(LlmProvider::class)::class;
            $checks['llm_provider_resolvable'] = true;
        } catch (Throwable $exception) {
            $runtime['probe']['error'] = [
                'message' => $this->redactor->redactString($exception->getMessage()),
                'type' => $exception::class,
            ];
        }

        if (
            ! $checks['coordinator_active_available']
            || ! $checks['required_specialists_available']
            || ! $checks['coordinator_profile_configured']
            || ! $checks['specialist_profiles_configured']
            || ! $checks['specialist_capabilities_present']
            || ! $checks['prompt_version_explicit']
            || ! $checks['llm_provider_resolvable']
        ) {
            return $this->buildReport($runtime, $checks);
        }

        $connection = DB::connection();
        $startingLevel = $connection->transactionLevel();
        $baselineCounts = $this->runtimeCounts();

        try {
            $connection->beginTransaction();

            $report = $this->companyLoop->run(
                coordinator: $coordinator,
                goal: (string) $runtime['probe']['goal'],
                context: [
                    'validation' => [
                        'type' => 'company_loop_production_acceptance',
                        'rolled_back' => true,
                    ],
                ],
            );

            $parent = Task::query()->find($report->parentTaskId);
            $children = Task::query()
                ->where('parent_task_id', $report->parentTaskId)
                ->orderBy('decomposition_index')
                ->get();
            $executions = Execution::query()->whereIn('task_id', $children->pluck('id'))->orderBy('task_id')->get();
            $artifacts = Artifact::query()
                ->where(function ($query) use ($children, $report): void {
                    $query->whereIn('task_id', $children->pluck('id'))
                        ->orWhere('id', $report->finalReportArtifactId);
                })
                ->orderBy('name')
                ->get();
            $communications = AgentCommunicationLog::query()
                ->whereIn('task_id', $children->pluck('id'))
                ->orderBy('message_type')
                ->get();

            $runtime['probe'] = [
                'goal' => $runtime['probe']['goal'],
                'parent_task_id' => $parent?->id,
                'child_task_ids' => $children->pluck('id')->all(),
                'execution_ids' => $executions->pluck('id')->all(),
                'artifact_names' => $artifacts->pluck('name')->values()->all(),
                'communication_types' => $communications->pluck('message_type')->values()->all(),
                'summary' => $report->summary,
                'error' => null,
            ];

            $checks['probe_completed'] = true;
            $checks['probe_child_tasks_completed'] = $children->count() === count(self::REQUIRED_SPECIALIST_ROLES)
                && $children->every(fn (Task $task): bool => $task->status === TaskStatus::Completed);
            $checks['probe_executions_succeeded'] = $executions->count() === count(self::REQUIRED_SPECIALIST_ROLES)
                && $executions->every(fn (Execution $execution): bool => $execution->status === ExecutionStatus::Succeeded);
            $checks['probe_artifacts_persisted'] = $artifacts->whereIn('name', ['structured_result', 'raw_response'])->count() === 6;
            $checks['probe_communications_persisted'] = $communications->pluck('message_type')->sort()->values()->all() === [
                'company_loop.handoff',
                'company_loop.handoff',
                'company_loop.handoff',
                'company_loop.result',
                'company_loop.result',
                'company_loop.result',
            ];
            $checks['probe_final_report_persisted'] = $artifacts->contains(
                fn (Artifact $artifact): bool => $artifact->id === $report->finalReportArtifactId && $artifact->name === 'coordinator_final_report',
            );
        } catch (Throwable $exception) {
            $runtime['probe']['error'] = [
                'message' => $this->redactor->redactString($exception->getMessage()),
                'type' => $exception::class,
            ];
        } finally {
            while ($connection->transactionLevel() > $startingLevel) {
                $connection->rollBack();
            }
        }

        $checks['probe_rolls_back_cleanly'] = $this->runtimeCounts() === $baselineCounts;

        return $this->buildReport($runtime, $checks);
    }

    /**
     * @param  array<string, mixed>  $runtime
     * @param  array<string, bool>  $checks
     * @return array<string, mixed>
     */
    private function buildReport(array $runtime, array $checks): array
    {
        return [
            'environment' => (string) config('app.env'),
            'required_specialist_roles' => self::REQUIRED_SPECIALIST_ROLES,
            'runtime' => $runtime,
            'checks' => $checks,
            'fallback' => [
                'safe' => true,
                'validation_runs_inside_transaction' => true,
                'probe_data_is_rolled_back' => true,
                'missing_runtime_prerequisites_fail_without_persisting_probe_data' => true,
            ],
            'ready' => ! in_array(false, $checks, true),
            'unavailable_reason' => $this->firstFailedCheck($checks),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function runtimeCounts(): array
    {
        return [
            'tasks' => Task::query()->count(),
            'executions' => Execution::query()->count(),
            'artifacts' => Artifact::query()->count(),
            'communications' => AgentCommunicationLog::query()->count(),
        ];
    }

    /**
     * @param  array<string, bool>  $checks
     */
    private function firstFailedCheck(array $checks): ?string
    {
        foreach ($checks as $check => $passed) {
            if (! $passed) {
                return $check;
            }
        }

        return null;
    }
}
