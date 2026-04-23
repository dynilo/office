<?php

namespace App\Application\Approvals\Services;

use App\Application\Approvals\Enums\ApprovalAction;
use App\Application\Approvals\Enums\ApprovalStatus;
use App\Application\Audit\Data\AuditActorData;
use App\Application\Audit\Data\AuditEventData;
use App\Application\Audit\Data\AuditSubjectData;
use App\Application\Audit\Services\AuditEventWriter;
use App\Application\Organizations\Services\OrganizationSettingsService;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\ApprovalRequest;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Support\Exceptions\EntityNotFoundException;
use App\Support\Exceptions\InvalidStateException;

final class HumanApprovalGateService
{
    public function __construct(
        private readonly OrganizationSettingsService $organizationSettings,
        private readonly AuditEventWriter $audit,
    ) {}

    public function assertExecutionCanStart(Task $task, Agent $agent): void
    {
        if (! $this->approvalRequired($task)) {
            return;
        }

        $approval = $this->latestApprovalRequest($task, ApprovalAction::ExecutionStart);

        if ($approval?->status === ApprovalStatus::Approved) {
            return;
        }

        if ($approval?->status === ApprovalStatus::Rejected) {
            throw new InvalidStateException('Execution is blocked by a rejected human approval request.');
        }

        if (! $approval instanceof ApprovalRequest) {
            $approval = ApprovalRequest::query()->create([
                'organization_id' => $task->organization_id,
                'task_id' => $task->id,
                'agent_id' => $agent->id,
                'action' => ApprovalAction::ExecutionStart->value,
                'status' => ApprovalStatus::Pending,
                'reason' => 'Organization policy requires human approval before execution can start.',
                'metadata' => [
                    'task_title' => $task->title,
                    'agent_role' => $agent->role,
                ],
                'requested_at' => now(),
            ]);

            $this->audit->write(new AuditEventData(
                eventName: 'approval.requested',
                subject: new AuditSubjectData('approval_request', $approval->id),
                actor: new AuditActorData('agent', $agent->id),
                source: 'human_approval_gate',
                metadata: [
                    'task_id' => $task->id,
                    'action' => $approval->action,
                    'status' => $approval->status->value,
                ],
            ));
        }

        throw new InvalidStateException('Execution requires human approval before it can start.');
    }

    public function approve(
        string $approvalRequestId,
        string $decidedByType = 'user',
        ?string $decidedById = null,
    ): ApprovalRequest {
        $approval = $this->findApprovalRequest($approvalRequestId);

        if ($approval->status !== ApprovalStatus::Pending) {
            throw new InvalidStateException('Only pending approval requests can be approved.');
        }

        $approval->status = ApprovalStatus::Approved;
        $approval->decided_at = now();
        $approval->decided_by_type = $decidedByType;
        $approval->decided_by_id = $decidedById;
        $approval->save();

        $this->audit->write(new AuditEventData(
            eventName: 'approval.approved',
            subject: new AuditSubjectData('approval_request', $approval->id),
            actor: $decidedById !== null ? new AuditActorData($decidedByType, $decidedById) : null,
            source: 'human_approval_gate',
            metadata: [
                'task_id' => $approval->task_id,
                'action' => $approval->action,
            ],
        ));

        return $approval->refresh();
    }

    public function reject(
        string $approvalRequestId,
        string $reason,
        string $decidedByType = 'user',
        ?string $decidedById = null,
    ): ApprovalRequest {
        $approval = $this->findApprovalRequest($approvalRequestId);

        if ($approval->status !== ApprovalStatus::Pending) {
            throw new InvalidStateException('Only pending approval requests can be rejected.');
        }

        $approval->status = ApprovalStatus::Rejected;
        $approval->reason = $reason;
        $approval->decided_at = now();
        $approval->decided_by_type = $decidedByType;
        $approval->decided_by_id = $decidedById;
        $approval->save();

        $this->audit->write(new AuditEventData(
            eventName: 'approval.rejected',
            subject: new AuditSubjectData('approval_request', $approval->id),
            actor: $decidedById !== null ? new AuditActorData($decidedByType, $decidedById) : null,
            source: 'human_approval_gate',
            metadata: [
                'task_id' => $approval->task_id,
                'action' => $approval->action,
                'reason' => $reason,
            ],
        ));

        return $approval->refresh();
    }

    public function latestApprovalRequest(Task $task, ApprovalAction $action): ?ApprovalRequest
    {
        return ApprovalRequest::query()
            ->where('task_id', $task->id)
            ->where('action', $action->value)
            ->latest('created_at')
            ->latest('id')
            ->first();
    }

    private function approvalRequired(Task $task): bool
    {
        if (! is_string($task->organization_id) || $task->organization_id === '') {
            return false;
        }

        $settings = $this->organizationSettings->resolve($task->organization_id);

        return (bool) ($settings->policy['approvals_required'] ?? false);
    }

    private function findApprovalRequest(string $approvalRequestId): ApprovalRequest
    {
        $approval = ApprovalRequest::query()->find($approvalRequestId);

        if (! $approval instanceof ApprovalRequest) {
            throw EntityNotFoundException::for('ApprovalRequest', $approvalRequestId);
        }

        return $approval;
    }
}
