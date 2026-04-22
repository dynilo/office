<?php

namespace App\Domain\Tasks\Data;

use App\Domain\Tasks\Enums\TaskPriority;
use App\Domain\Tasks\Enums\TaskStatus;
use InvalidArgumentException;

final readonly class TaskPayloadSummaryData
{
    public function __construct(
        public string $taskId,
        public string $title,
        public TaskStatus $status,
        public TaskPriority $priority,
        public int $inputCount,
    ) {
        if (trim($this->taskId) === '') {
            throw new InvalidArgumentException('Task ID cannot be empty.');
        }

        if (trim($this->title) === '') {
            throw new InvalidArgumentException('Task title cannot be empty.');
        }

        if ($this->inputCount < 0) {
            throw new InvalidArgumentException('Task input count cannot be negative.');
        }
    }

    public static function fromArray(array $attributes): self
    {
        return new self(
            taskId: (string) ($attributes['task_id'] ?? ''),
            title: (string) ($attributes['title'] ?? ''),
            status: TaskStatus::from($attributes['status']),
            priority: TaskPriority::from($attributes['priority']),
            inputCount: (int) ($attributes['input_count'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'task_id' => $this->taskId,
            'title' => $this->title,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'input_count' => $this->inputCount,
        ];
    }
}
