<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class IntegrationEvent extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'integration_provider_id', 'direction', 'external_reference',
        'subject_type', 'subject_id', 'status', 'retry_count', 'consent_reference',
        'summary', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function provider()
    {
        return $this->belongsTo(IntegrationProvider::class, 'integration_provider_id');
    }
}
