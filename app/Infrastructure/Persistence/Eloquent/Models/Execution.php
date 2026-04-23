<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Executions\Enums\ExecutionStatus;
use Database\Factories\ExecutionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Execution extends Model
{
    /** @use HasFactory<ExecutionFactory> */
    use HasFactory;
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'task_id',
        'agent_id',
        'idempotency_key',
        'status',
        'attempt',
        'input_snapshot',
        'output_payload',
        'provider_response',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExecutionStatus::class,
            'attempt' => 'integer',
            'input_snapshot' => 'array',
            'output_payload' => 'array',
            'provider_response' => 'array',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
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

    protected static function newFactory(): ExecutionFactory
    {
        return ExecutionFactory::new();
    }
}
