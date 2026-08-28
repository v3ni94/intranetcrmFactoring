<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'ticket_number', 'subject', 'category', 'status', 'priority',
        'created_by', 'assigned_to', 'organization_id', 'is_demo',
    ];

    protected $casts = [
        'is_demo' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }
}
