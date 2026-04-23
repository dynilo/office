<?php

namespace App\Models;

use Database\Factories\OrganizationSettingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSetting extends Model
{
    /** @use HasFactory<OrganizationSettingFactory> */
    use HasFactory;

    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'organization_id',
        'provider_settings',
        'memory_settings',
        'policy_settings',
        'runtime_defaults',
    ];

    protected function casts(): array
    {
        return [
            'provider_settings' => 'array',
            'memory_settings' => 'array',
            'policy_settings' => 'array',
            'runtime_defaults' => 'array',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    protected static function newFactory(): OrganizationSettingFactory
    {
        return OrganizationSettingFactory::new();
    }
}
