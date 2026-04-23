<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Agents\Enums\AgentStatus;
use Database\Factories\AgentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Agent extends Model
{
    /** @use HasFactory<AgentFactory> */
    use HasFactory;
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'key',
        'name',
        'role',
        'version',
        'status',
        'description',
        'capabilities',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => AgentStatus::class,
            'capabilities' => 'array',
            'metadata' => 'array',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(AgentProfile::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function assignmentDecisions(): HasMany
    {
        return $this->hasMany(TaskAssignmentDecision::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(Execution::class);
    }

    public function sentCommunications(): HasMany
    {
        return $this->hasMany(AgentCommunicationLog::class, 'sender_agent_id');
    }

    public function receivedCommunications(): HasMany
    {
        return $this->hasMany(AgentCommunicationLog::class, 'recipient_agent_id');
    }

    protected static function newFactory(): AgentFactory
    {
        return AgentFactory::new();
    }

    protected static function booted(): void
    {
        static::saving(function (self $agent): void {
            if ($agent->code !== null && $agent->key === null) {
                $agent->key = $agent->code;
            }
        });
    }
}
