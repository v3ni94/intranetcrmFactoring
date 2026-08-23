<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CapTableScenario extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'scenario_key', 'label', 'status', 'description', 'is_demo'];

    protected $casts = ['is_demo' => 'boolean'];

    public function equityInstruments()
    {
        return $this->hasMany(EquityInstrument::class);
    }
}
