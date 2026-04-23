<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Models\Organization;
use App\Support\Tenancy\BelongsToOrganization;
use Database\Factories\DeadLetterRecordFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeadLetterRecord extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<DeadLetterRecordFactory> */
    use HasFactory;

    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'organization_id',
        'task_id',
        'agent_id',
        'execution_id',
        'reason_code',
        'error_message',
        'payload',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'captured_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(Execution::class);
    }

    protected static function newFactory(): DeadLetterRecordFactory
    {
        return DeadLetterRecordFactory::new();
    }
}
