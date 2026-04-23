<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Support\Tenancy\BelongsToOrganization;
use Database\Factories\AgentProfileFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentProfile extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<AgentProfileFactory> */
    use HasFactory;
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'organization_id',
        'agent_id',
        'system_prompt',
        'model_preference',
        'temperature_policy',
        'instructions',
        'defaults',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'temperature_policy' => 'array',
            'instructions' => 'array',
            'defaults' => 'array',
            'metadata' => 'array',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    protected static function newFactory(): AgentProfileFactory
    {
        return AgentProfileFactory::new();
    }
}
