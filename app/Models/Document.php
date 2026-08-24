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
     * Interne Rollen sehen alles. Externe Rollen strikt default-deny: Kunde sieht nur
     * extern freigegebene Dokumente der eigenen Organisation; Investor/Beirat nur
     * Board-Pack-Dokumente bzw. explizit fuer ihre Zielgruppe freigegebene Dokumente.
     * Abgelaufene Freigaben (release_expires_at) sind fuer Externe nicht mehr sichtbar.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $roles = $user->getRoleNames()->all();

        if (array_intersect($roles, RoleCatalog::INTERNAL_ROLES)) {
            return $query;
        }

        $query->where('visibility', 'extern_freigegeben')
            ->where(function (Builder $q) {
                $q->whereNull('release_expires_at')->orWhere('release_expires_at', '>=', now()->startOfDay());
            });

        if (array_intersect($roles, ['kunde_admin', 'kunde_sachbearbeitung'])) {
            return $query->where('related_type', Organization::class)
                ->where('related_id', $user->customer_org_id ?? 0);
        }

        $audiences = array_values(array_intersect($roles, ['investor', 'beirat']));

        return $query->where(function (Builder $q) use ($audiences) {
            $q->where('category', 'board_pack');
            if ($audiences !== []) {
                $q->orWhereIn('release_audience', $audiences);
            }
        });
    }
}
