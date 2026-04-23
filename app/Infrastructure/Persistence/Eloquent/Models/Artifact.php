<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Support\Tenancy\BelongsToOrganization;
use Database\Factories\ArtifactFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Artifact extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<ArtifactFactory> */
    use HasFactory;
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'organization_id',
        'task_id',
        'execution_id',
        'kind',
        'name',
        'content_text',
        'content_json',
        'file_metadata',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'content_json' => 'array',
            'file_metadata' => 'array',
            'metadata' => 'array',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(Execution::class);
    }

    protected static function newFactory(): ArtifactFactory
    {
        return ArtifactFactory::new();
    }
}
