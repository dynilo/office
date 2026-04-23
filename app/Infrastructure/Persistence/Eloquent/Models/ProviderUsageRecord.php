<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\ProviderUsageRecordFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderUsageRecord extends Model
{
    /** @use HasFactory<ProviderUsageRecordFactory> */
    use HasFactory;

    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'execution_id',
        'task_id',
        'agent_id',
        'provider',
        'model',
        'response_id',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'estimated_cost_micros',
        'currency',
        'pricing_source',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'total_tokens' => 'integer',
            'estimated_cost_micros' => 'integer',
            'metadata' => 'array',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(Execution::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    protected static function newFactory(): ProviderUsageRecordFactory
    {
        return ProviderUsageRecordFactory::new();
    }
}
