<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use BelongsToTenant;

    public const STATUSES = [
        'Lead', 'Qualifiziert', 'Antrag begonnen', 'Unterlagen fehlen', 'KYC/KYB laeuft',
        'Kreditanalyse', 'Angebot', 'Vertrag/Freigabe', 'Technische Einrichtung', 'Aktiv', 'Verloren',
    ];

    protected $fillable = [
        'tenant_id', 'organization_id', 'company_name', 'specialty', 'contact_name',
        'contact_email', 'contact_phone', 'source', 'status', 'owner_id', 'notes', 'is_demo',
    ];

    protected $casts = ['is_demo' => 'boolean'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }

    public function activities()
    {
        return $this->hasMany(CrmActivity::class);
    }
}
