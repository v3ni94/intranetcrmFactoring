<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Workstream extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'code', 'title', 'owner_id', 'deputy_id', 'deliverables', 'due_date', 'status', 'is_demo',
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_demo' => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function deputy()
    {
        return $this->belongsTo(User::class, 'deputy_id');
    }

    public function risks()
    {
        return $this->hasMany(ProjectRisk::class);
    }
}
