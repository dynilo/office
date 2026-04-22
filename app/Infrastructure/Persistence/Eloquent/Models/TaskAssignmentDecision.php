<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAssignmentDecision extends Model
{
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'task_id',
        'agent_id',
        'outcome',
        'reason_code',
        'matched_by',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
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
}
