<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class IntegrationProvider extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'key', 'category', 'name', 'mode', 'status', 'mapping_version',
        'last_success_at', 'active',
    ];

    protected $casts = [
        'last_success_at' => 'datetime',
        'active' => 'boolean',
    ];

    public function events()
    {
        return $this->hasMany(IntegrationEvent::class);
    }
}
