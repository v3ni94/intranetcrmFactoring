<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'account_name', 'bank_name', 'iban_masked', 'bic', 'currency',
        'balance_amount', 'purpose', 'is_demo',
    ];

    protected $casts = [
        'balance_amount' => 'decimal:4',
        'is_demo' => 'boolean',
    ];

    public function transactions()
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function payoutBatches()
    {
        return $this->hasMany(PayoutBatch::class);
    }
}
