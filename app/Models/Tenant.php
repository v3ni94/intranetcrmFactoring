<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = ['name', 'slug', 'type', 'is_demo', 'demo_seed_id'];

    protected $casts = [
        'is_demo' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function isDemo(): bool
    {
        return $this->is_demo;
    }
}
