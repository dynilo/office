<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Application\Approvals\Enums\ApprovalStatus;
use App\Support\Tenancy\BelongsToOrganization;
use Database\Factories\ApprovalRequestFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRequest extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<ApprovalRequestFactory> */
    use HasFactory;

    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'organization_id',
        'task_id',
        'agent_id',
        'action',
        'status',
        'reason',
        'metadata',
        'requested_at',
        'decided_at',
        'decided_by_type',
        'decided_by_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApprovalStatus::class,
            'metadata' => 'array',
            'requested_at' => 'immutable_datetime',
            'decided_at' => 'immutable_datetime',
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

    protected static function newFactory(): ApprovalRequestFactory
    {
        return ApprovalRequestFactory::new();
    }
}
