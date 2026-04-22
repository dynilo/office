<?php

namespace App\Domain\Executions\Data;

use App\Domain\Executions\Enums\ExecutionStatus;
use InvalidArgumentException;

final readonly class ExecutionResultSummaryData
{
    public function __construct(
        public string $executionId,
        public ExecutionStatus $status,
        public ?string $outputReference,
        public ?string $errorMessage,
    ) {
        if (trim($this->executionId) === '') {
            throw new InvalidArgumentException('Execution ID cannot be empty.');
        }

        if ($this->errorMessage !== null && trim($this->errorMessage) === '') {
            throw new InvalidArgumentException('Execution error message cannot be blank when provided.');
        }
    }

    public static function fromArray(array $attributes): self
    {
        return new self(
            executionId: (string) ($attributes['execution_id'] ?? ''),
            status: ExecutionStatus::from($attributes['status']),
            outputReference: isset($attributes['output_reference']) ? (string) $attributes['output_reference'] : null,
            errorMessage: isset($attributes['error_message']) ? (string) $attributes['error_message'] : null,
        );
    }

    public function isSuccessful(): bool
    {
        return $this->status === ExecutionStatus::Succeeded;
    }

    public function toArray(): array
    {
        return [
            'execution_id' => $this->executionId,
            'status' => $this->status->value,
            'output_reference' => $this->outputReference,
            'error_message' => $this->errorMessage,
        ];
    }
}
