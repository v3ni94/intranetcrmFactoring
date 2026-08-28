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

    public function primaryRoleLabel(): string
    {
        return $this->getRoleNames()->first() ?? 'Ohne Rolle';
    }
}
