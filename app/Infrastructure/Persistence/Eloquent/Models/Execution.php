<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Executions\Enums\ExecutionStatus;
use App\Support\Tenancy\BelongsToOrganization;
use Database\Factories\ExecutionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Execution extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<ExecutionFactory> */
    use HasFactory;
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'organization_id',
        'task_id',
        'agent_id',
        'idempotency_key',
        'retry_of_execution_id',
        'status',
        'attempt',
        'retry_count',
        'max_retries',
        'input_snapshot',
        'output_payload',
        'provider_response',
        'error_message',
        'failure_classification',
        'started_at',
        'finished_at',
        'next_retry_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExecutionStatus::class,
            'attempt' => 'integer',
            'retry_count' => 'integer',
            'max_retries' => 'integer',
            'input_snapshot' => 'array',
            'output_payload' => 'array',
            'provider_response' => 'array',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
            'next_retry_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ExecutionLog::class);
    }

    public function artifacts(): HasMany
    {
        return $this->hasMany(Artifact::class);
    }

    public function providerUsageRecords(): HasMany
    {
        return $this->hasMany(ProviderUsageRecord::class);
    }

    public function retryOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'retry_of_execution_id');
    }

    public function retries(): HasMany
    {
        return $this->hasMany(self::class, 'retry_of_execution_id');
    }

    protected static function newFactory(): ExecutionFactory
    {
        return ExecutionFactory::new();
    }
}
