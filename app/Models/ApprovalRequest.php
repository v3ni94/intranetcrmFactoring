<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'subject_type', 'subject_id', 'action', 'status', 'requested_by',
        'decided_by', 'reason', 'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function subject()
    {
        return $this->morphTo();
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
