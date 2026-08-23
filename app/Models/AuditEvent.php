<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'user_id', 'user_role', 'action', 'subject_type', 'subject_id',
        'old_values', 'new_values', 'reason', 'ip_address', 'session_id',
        'previous_hash', 'hash', 'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
