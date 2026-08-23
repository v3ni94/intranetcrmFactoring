<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class DebtorLimit extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'debtor_organization_id', 'customer_organization_id', 'limit_amount',
        'used_amount', 'status', 'valid_until', 'is_demo',
    ];

    protected $casts = [
        'limit_amount' => 'decimal:4',
        'used_amount' => 'decimal:4',
        'valid_until' => 'date',
        'is_demo' => 'boolean',
    ];

    public function debtorOrganization()
    {
        return $this->belongsTo(Organization::class, 'debtor_organization_id');
    }

    public function customerOrganization()
    {
        return $this->belongsTo(Organization::class, 'customer_organization_id');
    }
}
