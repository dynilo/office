<?php

namespace App\Application\CompanyLoop\Data;

final readonly class CompanyLoopReportData
{
    /**
     * @param  array<int, array<string, mixed>>  $childReports
     */
    public function __construct(
        public string $goal,
        public string $status,
        public string $parentTaskId,
        public int $childTaskCount,
        public array $childReports,
        public string $summary,
        public ?string $finalReportArtifactId = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'goal' => $this->goal,
            'status' => $this->status,
            'parent_task_id' => $this->parentTaskId,
            'child_task_count' => $this->childTaskCount,
            'child_reports' => $this->childReports,
            'summary' => $this->summary,
            'final_report_artifact_id' => $this->finalReportArtifactId,
        ];
    }
}
