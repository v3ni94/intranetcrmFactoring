<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CreditLine extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'organization_id', 'contract_id', 'line_type', 'limit_amount', 'used_amount',
        'status', 'valid_from', 'valid_until', 'decided_by', 'decision_reason', 'is_demo',
    ];

    protected $casts = [
        'limit_amount' => 'decimal:4',
        'used_amount' => 'decimal:4',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_demo' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function availableAmount(): float
    {
        return (float) $this->limit_amount - (float) $this->used_amount;
    }

    public function utilizationPercent(): float
    {
        if ((float) $this->limit_amount <= 0) {
            return 0;
        }

        return round(((float) $this->used_amount / (float) $this->limit_amount) * 100, 2);
    }
}
