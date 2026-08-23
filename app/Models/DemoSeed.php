<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class DemoSeed extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'seed_id', 'label', 'generated_at'];

    protected $casts = ['generated_at' => 'datetime'];
}
