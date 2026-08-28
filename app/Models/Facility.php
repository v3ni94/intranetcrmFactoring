<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'investor_organization_id', 'facility_number', 'name', 'commitment_amount',
        'drawn_amount', 'interest_rate_percent', 'commitment_fee_percent', 'start_date',
        'maturity_date', 'seniority', 'covenants', 'status', 'detail_level', 'is_demo',
        'early_termination_right', 'termination_notice_days', 'terminated_at', 'termination_reason',
    ];

    protected $casts = [
        'commitment_amount' => 'decimal:4',
        'drawn_amount' => 'decimal:4',
        'start_date' => 'date',
        'maturity_date' => 'date',
        'covenants' => 'array',
        'is_demo' => 'boolean',
        'early_termination_right' => 'boolean',
        'terminated_at' => 'datetime',
    ];

    public function investorOrganization()
    {
        return $this->belongsTo(Organization::class, 'investor_organization_id');
    }

    public function events()
    {
        return $this->hasMany(FacilityEvent::class);
    }

    public function undrawnAmount(): float
    {
        return (float) $this->commitment_amount - (float) $this->drawn_amount;
    }

    public function utilizationPercent(): float
    {
        if ((float) $this->commitment_amount <= 0) {
            return 0;
        }

        return round(((float) $this->drawn_amount / (float) $this->commitment_amount) * 100, 2);
    }
}
