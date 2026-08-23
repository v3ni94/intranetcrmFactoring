<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class FactoringProduct extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'recourse_type', 'service_type', 'disclosure_type', 'scope_type', 'active',
    ];

    protected $casts = ['active' => 'boolean'];

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
