<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class PayoutBatch extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'batch_number', 'bank_account_id', 'total_amount', 'item_count', 'status',
        'sepa_export_reference', 'approved_by_first', 'approved_by_second', 'executed_at', 'is_demo',
    ];

    protected $casts = [
        'total_amount' => 'decimal:4',
        'executed_at' => 'datetime',
        'is_demo' => 'boolean',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }
}
