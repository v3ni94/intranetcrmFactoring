<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'organization_id', 'factoring_product_id', 'contract_number', 'version',
        'start_date', 'term_months', 'notice_period_days', 'status', 'purchase_line', 'payout_line',
        'advance_rate_percent', 'reserve_percent', 'factoring_fee_percent', 'service_fee_percent',
        'minimum_fee', 'setup_fee', 'interest_basis', 'reference_rate_percent', 'margin_percent',
        'interest_floor_percent', 'interest_cap_percent', 'day_count_convention', 'max_days_outstanding',
        'recourse_period_days', 'excluded_debtor_types', 'approved_by', 'approved_at', 'is_demo',
    ];

    protected $casts = [
        'start_date' => 'date',
        'approved_at' => 'datetime',
        'excluded_debtor_types' => 'array',
        'purchase_line' => 'decimal:4',
        'payout_line' => 'decimal:4',
        'advance_rate_percent' => 'decimal:2',
        'reserve_percent' => 'decimal:2',
        'factoring_fee_percent' => 'decimal:3',
        'is_demo' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function factoringProduct()
    {
        return $this->belongsTo(FactoringProduct::class);
    }

    public function receivables()
    {
        return $this->hasMany(Receivable::class);
    }

    public function creditLines()
    {
        return $this->hasMany(CreditLine::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'aktiv';
    }
}
