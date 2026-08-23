<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'entry_number', 'booking_date', 'value_date', 'event_type', 'source_type',
        'source_id', 'reverses_entry_id', 'created_by', 'is_demo',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'value_date' => 'date',
        'is_demo' => 'boolean',
    ];

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function isBalanced(): bool
    {
        return round((float) $this->lines->sum('debit_amount'), 2) === round((float) $this->lines->sum('credit_amount'), 2);
    }
}
