<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    use BelongsToTenant;

    public const STAGES = ['Qualifizierung', 'Bedarfsanalyse', 'Angebot', 'Verhandlung', 'Gewonnen', 'Verloren'];

    protected $fillable = [
        'tenant_id', 'lead_id', 'organization_id', 'name', 'expected_volume',
        'probability_percent', 'stage', 'expected_close_date', 'next_action', 'owner_id', 'is_demo',
    ];

    protected $casts = [
        'expected_volume' => 'decimal:4',
        'expected_close_date' => 'date',
        'is_demo' => 'boolean',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
