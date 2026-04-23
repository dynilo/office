<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy\BelongsToOrganization;
use Database\Factories\UsageAccountingRecordFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageAccountingRecord extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<UsageAccountingRecordFactory> */
    use HasFactory;

    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'organization_id',
        'user_id',
        'agent_id',
        'task_id',
        'execution_id',
        'metric_key',
        'quantity',
        'metadata',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'metadata' => 'array',
            'recorded_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(Execution::class);
    }

    protected static function newFactory(): UsageAccountingRecordFactory
    {
        return UsageAccountingRecordFactory::new();
    }
}
