<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'payout_batch_id', 'purchase_id', 'organization_id', 'amount',
        'idempotency_key', 'status', 'confirmed_at', 'is_demo',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'confirmed_at' => 'datetime',
        'is_demo' => 'boolean',
    ];

    public function batch()
    {
        return $this->belongsTo(PayoutBatch::class, 'payout_batch_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
