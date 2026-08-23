<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class DunningCase extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'receivable_id', 'case_type', 'dunning_level', 'status', 'reason',
        'open_amount', 'assignee_id', 'next_action_date', 'is_demo',
    ];

    protected $casts = [
        'open_amount' => 'decimal:4',
        'next_action_date' => 'date',
        'is_demo' => 'boolean',
    ];

    public function receivable()
    {
        return $this->belongsTo(Receivable::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
