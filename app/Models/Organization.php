<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'org_type', 'name', 'legal_form', 'register_number', 'tax_number',
        'specialty', 'street', 'zip', 'city', 'country', 'iban_masked', 'customer_status',
        'risk_class', 'pseudonym_id', 'account_manager_id', 'is_demo',
    ];

    protected $casts = [
        'is_demo' => 'boolean',
    ];

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function beneficialOwners()
    {
        return $this->hasMany(BeneficialOwner::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function receivables()
    {
        return $this->hasMany(Receivable::class);
    }

    public function creditLines()
    {
        return $this->hasMany(CreditLine::class);
    }

    public function kycCases()
    {
        return $this->hasMany(KycCase::class);
    }

    public function accountManager()
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    public function scopeCustomers($query)
    {
        return $query->where('org_type', 'customer');
    }

    public function scopeDebtors($query)
    {
        return $query->where('org_type', 'debtor');
    }

    public function scopeInvestors($query)
    {
        return $query->where('org_type', 'investor');
    }
}
