<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Shareholder extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'type', 'notes', 'is_demo'];

    protected $casts = ['is_demo' => 'boolean'];

    public function equityInstruments()
    {
        return $this->hasMany(EquityInstrument::class);
    }
}
