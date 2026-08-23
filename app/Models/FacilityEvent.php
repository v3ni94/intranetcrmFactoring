<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class FacilityEvent extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'facility_id', 'event_type', 'amount', 'event_date', 'covenant_status',
        'notes', 'is_demo',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'event_date' => 'date',
        'is_demo' => 'boolean',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
