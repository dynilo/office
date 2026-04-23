<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Tasks\Enums\TaskPriority;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Support\Tenancy\BelongsToOrganization;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<TaskFactory> */
    use HasFactory;
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'organization_id',
        'agent_id',
        'parent_task_id',
        'decomposition_index',
        'title',
        'summary',
        'description',
        'status',
        'priority',
        'source',
        'requested_agent_role',
        'payload',
        'context',
        'submitted_at',
        'scheduled_at',
        'due_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'decomposition_index' => 'integer',
            'payload' => 'array',
            'context' => 'array',
            'submitted_at' => 'immutable_datetime',
            'scheduled_at' => 'immutable_datetime',
            'due_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_task_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_task_id')->orderBy('decomposition_index');
    }

    public function assignmentDecisions(): HasMany
    {
        return $this->hasMany(TaskAssignmentDecision::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(Execution::class);
    }

    public function providerUsageRecords(): HasMany
    {
        return $this->hasMany(ProviderUsageRecord::class);
    }

    public function artifacts(): HasMany
    {
        return $this->hasMany(Artifact::class);
    }

    public function communicationLogs(): HasMany
    {
        return $this->hasMany(AgentCommunicationLog::class);
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'task_dependencies',
            'task_id',
            'depends_on_task_id',
        )->withTimestamps();
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'task_dependencies',
            'depends_on_task_id',
            'task_id',
        )->withTimestamps();
    }

    protected static function newFactory(): TaskFactory
    {
        return TaskFactory::new();
    }
}
