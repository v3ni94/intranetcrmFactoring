<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class RelatedParty extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'relation_type', 'description', 'conflict_status', 'mitigation', 'is_demo',
    ];

    protected $casts = ['is_demo' => 'boolean'];
}
