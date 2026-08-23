<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'receivable_id', 'nominal_amount', 'purchasable_amount', 'advance_rate_percent',
        'immediate_payout_amount', 'reserve_amount', 'factoring_fee_amount', 'expected_interest_amount',
        'deductions_amount', 'deduction_reason', 'status', 'approved_by_first', 'approved_by_second',
        'purchased_at', 'is_demo',
    ];

    protected $casts = [
        'nominal_amount' => 'decimal:4',
        'purchasable_amount' => 'decimal:4',
        'immediate_payout_amount' => 'decimal:4',
        'reserve_amount' => 'decimal:4',
        'factoring_fee_amount' => 'decimal:4',
        'expected_interest_amount' => 'decimal:4',
        'deductions_amount' => 'decimal:4',
        'purchased_at' => 'datetime',
        'is_demo' => 'boolean',
    ];

    public function receivable()
    {
        return $this->belongsTo(Receivable::class);
    }

    public function payout()
    {
        return $this->hasOne(Payout::class);
    }

    public function needsSecondApproval(): bool
    {
        return $this->approved_by_first && ! $this->approved_by_second;
    }
}
