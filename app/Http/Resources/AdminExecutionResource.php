<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminExecutionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'agent_id' => $this->agent_id,
            'status' => $this->status?->value,
            'attempt' => $this->attempt,
            'retry_count' => $this->retry_count,
            'max_retries' => $this->max_retries,
            'failure_classification' => $this->failure_classification,
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'next_retry_at' => $this->next_retry_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
