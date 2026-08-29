<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\RoleCatalog;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'company_name',
        'customer_org_id',
        'is_demo',
        'is_active',
        'position', 'department', 'supervisor_id', 'disciplinary_supervisor_id',
        'phone_business', 'phone_private', 'email_private',
        'street', 'zip', 'city', 'country', 'birth_date',
        'tax_id', 'id_card_number', 'id_card_valid_until',
        'criminal_record_check_at', 'drivers_license_class', 'drivers_license_valid_until',
        'schufa_check_at', 'hr_notes', 'joined_at', 'left_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'tax_id',
        'id_card_number',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_demo' => 'boolean',
            'is_active' => 'boolean',
            'birth_date' => 'date',
            'tax_id' => 'encrypted',
            'id_card_number' => 'encrypted',
            'id_card_valid_until' => 'date',
            'criminal_record_check_at' => 'date',
            'drivers_license_valid_until' => 'date',
            'schufa_check_at' => 'date',
            'joined_at' => 'date',
            'left_at' => 'date',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * MFA ist Pflicht fuer alle internen, Investor- und Beirats-Rollen (Abschnitt 18).
     * Kunden-Rollen sind im Prototyp ausgenommen (geringeres Schutzniveau der Kontodaten).
     */
    public function requiresMfa(): bool
    {
        return $this->hasAnyRole(array_merge(RoleCatalog::INTERNAL_ROLES, ['investor', 'beirat']));
    }

    public function hasConfirmedTwoFactor(): bool
    {
        return ! is_null($this->two_factor_confirmed_at);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'customer_org_id');
    }

    /** Nachweis-Dokumente der Personalakte (v3.04). */
    public function hrDocuments()
    {
        return $this->hasMany(HrDocument::class)->orderBy('doc_type')->orderByDesc('created_at');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function disciplinarySupervisor()
    {
        return $this->belongsTo(User::class, 'disciplinary_supervisor_id');
    }

    /**
     * Beschaeftigungsfenster (v3.02): Konten sind erst ab Eintrittsdatum nutzbar
     * und nach Austrittsdatum automatisch gesperrt — ohne Cron, zur Laufzeit
     * geprueft (Login und 2FA-Challenge).
     */
    public function isWithinEmploymentPeriod(): bool
    {
        if ($this->joined_at && $this->joined_at->isFuture()) {
            return false;
        }
        if ($this->left_at && $this->left_at->isPast() && ! $this->left_at->isToday()) {
            return false;
        }

        return true;
    }

    /** Effektiver Kontostatus fuer Anzeige und Zugangspruefung. */
    public function effectiveStatus(): string
    {
        if (! $this->is_active) {
            return 'deaktiviert';
        }
        if ($this->joined_at && $this->joined_at->isFuture()) {
            return 'wartet_auf_eintritt';
        }
        if ($this->left_at && $this->left_at->isPast() && ! $this->left_at->isToday()) {
            return 'ausgetreten';
        }

        return 'aktiv';
    }

    public function primaryRoleLabel(): string
    {
        return $this->getRoleNames()->first() ?? 'Ohne Rolle';
    }
}
