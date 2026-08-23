<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'title', 'description', 'related_type', 'related_id',
        'assignee_id', 'due_date', 'status', 'priority', 'is_demo',
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_demo' => 'boolean',
    ];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function related()
    {
        return $this->morphTo();
    }
}
