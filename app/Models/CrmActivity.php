<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CrmActivity extends Model
{
    use BelongsToTenant;

    protected $table = 'crm_activities';

    protected $fillable = [
        'tenant_id', 'lead_id', 'opportunity_id', 'organization_id', 'type', 'subject',
        'body', 'user_id', 'occurred_at', 'is_demo',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'is_demo' => 'boolean',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
