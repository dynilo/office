<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Support\Tenancy\BelongsToOrganization;
use Database\Factories\ExecutionLogFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutionLog extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<ExecutionLogFactory> */
    use HasFactory;
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'organization_id',
        'execution_id',
        'sequence',
        'level',
        'message',
        'context',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'context' => 'array',
            'logged_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(Execution::class);
    }

    protected static function newFactory(): ExecutionLogFactory
    {
        return ExecutionLogFactory::new();
    }
}
