<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class JournalLine extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'journal_entry_id', 'account_code', 'account_name', 'debit_amount',
        'credit_amount', 'currency', 'organization_id', 'contract_id',
    ];

    protected $casts = [
        'debit_amount' => 'decimal:4',
        'credit_amount' => 'decimal:4',
    ];

    public function entry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
