<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class BeneficialOwner extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'organization_id', 'first_name', 'last_name', 'ownership_percent',
        'birth_date', 'nationality', 'pep_status', 'sanctions_hit', 'screened_at', 'is_demo',
    ];

    protected $casts = [
        'pep_status' => 'boolean',
        'sanctions_hit' => 'boolean',
        'birth_date' => 'date',
        'screened_at' => 'datetime',
        'is_demo' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
