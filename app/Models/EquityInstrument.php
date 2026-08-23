<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class EquityInstrument extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'shareholder_id', 'cap_table_scenario_id', 'instrument_type',
        'nominal_amount', 'percentage', 'version', 'valid_from', 'status', 'is_demo',
    ];

    protected $casts = [
        'nominal_amount' => 'decimal:4',
        'percentage' => 'decimal:3',
        'valid_from' => 'date',
        'is_demo' => 'boolean',
    ];

    public function shareholder()
    {
        return $this->belongsTo(Shareholder::class);
    }

    public function scenario()
    {
        return $this->belongsTo(CapTableScenario::class, 'cap_table_scenario_id');
    }
}
