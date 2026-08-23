<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'bank_transaction_id', 'receivable_id', 'amount', 'type',
        'match_confidence_percent', 'match_reason', 'matched_by', 'matched_at', 'is_demo',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'match_confidence_percent' => 'decimal:2',
        'matched_at' => 'datetime',
        'is_demo' => 'boolean',
    ];

    public function bankTransaction()
    {
        return $this->belongsTo(BankTransaction::class);
    }

    public function receivable()
    {
        return $this->belongsTo(Receivable::class);
    }

    public function matchedBy()
    {
        return $this->belongsTo(User::class, 'matched_by');
    }
}
