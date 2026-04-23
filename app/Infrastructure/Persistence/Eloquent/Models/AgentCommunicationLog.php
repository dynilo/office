<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\AgentCommunicationLogFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentCommunicationLog extends Model
{
    /** @use HasFactory<AgentCommunicationLogFactory> */
    use HasFactory;
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'sender_agent_id',
        'recipient_agent_id',
        'task_id',
        'message_type',
        'subject',
        'body',
        'metadata',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'sent_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'sender_agent_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'recipient_agent_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    protected static function newFactory(): AgentCommunicationLogFactory
    {
        return AgentCommunicationLogFactory::new();
    }
}
