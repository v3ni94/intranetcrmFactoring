<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'bank_account_id', 'value_date', 'amount', 'reference', 'counterparty_name',
        'import_source', 'status', 'is_demo',
    ];

    protected $casts = [
        'value_date' => 'date',
        'amount' => 'decimal:4',
        'is_demo' => 'boolean',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
