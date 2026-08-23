<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\RoleCatalog;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Erzwingt die Dokumentsichtbarkeit nach Rolle (Medical Data Firewall, Abschnitt 16.2).
     * Interne Rollen sehen alles; Kunde/Investor/Beirat ausschliesslich extern freigegebene
     * Dokumente, Kunde zusaetzlich nur eigene organisationsbezogene Dokumente.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $roles = $user->getRoleNames()->all();

        if (array_intersect($roles, RoleCatalog::INTERNAL_ROLES)) {
            return $query;
        }

        return $query->where('visibility', 'extern_freigegeben')
            ->where(function (Builder $q) use ($user) {
                $q->where(function (Builder $q2) {
                    $q2->whereNull('related_type')->orWhere('related_type', '!=', Organization::class);
                })->orWhere(function (Builder $q2) use ($user) {
                    $q2->where('related_type', Organization::class)->where('related_id', $user->customer_org_id);
                });
            });
    }
}
