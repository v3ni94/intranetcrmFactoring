<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class FinancialScenario extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'scenario_key', 'label', 'portfolio_year1_eur', 'growth_yoy_percent',
        'factoring_fee_percent', 'dso_days', 'advance_rate_percent', 'risk_cost_percent',
        'debt_interest_percent', 'customer_interest_percent', 'opex_factor', 'source_document',
        'status', 'is_demo',
    ];

    protected $casts = [
        'portfolio_year1_eur' => 'decimal:4',
        'is_demo' => 'boolean',
    ];
}
