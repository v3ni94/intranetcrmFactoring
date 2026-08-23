<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'title', 'category', 'related_type', 'related_id', 'version', 'storage_path',
        'visibility', 'release_purpose', 'release_audience', 'release_expires_at', 'export_locked',
        'owner_id', 'released_by', 'is_demo',
    ];

    protected $casts = [
        'release_expires_at' => 'date',
        'export_locked' => 'boolean',
        'is_demo' => 'boolean',
    ];

    public function related()
    {
        return $this->morphTo();
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function isExternallyReleased(): bool
    {
        return $this->visibility === 'extern_freigegeben';
    }
}
