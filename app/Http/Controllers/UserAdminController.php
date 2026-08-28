<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeUserMail;
use App\Models\Organization;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\RoleCatalog;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Benutzerverwaltung (Systemadministration/Geschaeftsleitung): Mitarbeiter,
 * Kunden-Nutzer, Investoren und Beiraete anlegen, Rollen zuordnen und Konten
 * deaktivieren. Kein Loeschen — deaktivierte Konten bleiben fuer den
 * Audit-Trail erhalten, koennen sich aber nicht mehr anmelden.
 */
class UserAdminController extends Controller
{
    private const CUSTOMER_ROLES = ['kunde_admin', 'kunde_sachbearbeitung'];

    public function index()
    {
        $users = User::with('roles', 'organization')
            ->orderBy('name')
            ->paginate(25);

        $organizations = Organization::where('org_type', 'customer')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('users.index', [
            'users' => $users,
            'roles' => RoleCatalog::ROLES,
            'customerRoles' => self::CUSTOMER_ROLES,
            'organizations' => $organizations,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email:strict|max:255|unique:users,email',
            'role' => ['required', Rule::in(array_keys(RoleCatalog::ROLES))],
            'customer_org_id' => [
                Rule::requiredIf(in_array($request->input('role'), self::CUSTOMER_ROLES, true)),
                'nullable', 'exists:organizations,id',
            ],
        ]);

        // Superadmin darf nur durch Superadmin vergeben werden.
        if ($data['role'] === 'superadmin_demo' && ! $request->user()->hasRole('superadmin_demo')) {
            return back()->withErrors(['role' => 'Diese Rolle kann nur durch einen Superadmin vergeben werden.'])->withInput();
        }

        // Zufaelliges Startpasswort; der Nutzer setzt sein eigenes ueber den
        // per E-Mail versendeten Setz-Link (kein Klartext-Passwort per Mail).
        $initialPassword = Str::password(16);

        $user = User::create([
            'tenant_id' => TenantContext::id(),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($initialPassword),
            'customer_org_id' => in_array($data['role'], self::CUSTOMER_ROLES, true) ? $data['customer_org_id'] : null,
            'is_demo' => false,
            'is_active' => true,
        ]);
        $user->syncRoles([$data['role']]);

        AuditLogger::log('create', User::class, $user->id, [], [
            'email' => $user->email, 'role' => $data['role'], 'customer_org_id' => $user->customer_org_id,
        ], 'Benutzer angelegt');

        if ($this->sendWelcomeMail($user)) {
            return redirect()->route('users.index')
                ->with('status', "Benutzer {$user->email} angelegt. Die Zugangsdaten (Passwort-Setz-Link) wurden per E-Mail versendet.");
        }

        // Fallback bei fehlgeschlagenem Mailversand (z.B. SMTP nicht konfiguriert):
        // Startpasswort einmalig anzeigen, damit der Zugang uebergeben werden kann.
        return redirect()->route('users.index')
            ->with('created_user', ['email' => $user->email, 'password' => $initialPassword])
            ->with('status', "Benutzer {$user->email} angelegt. E-Mail-Versand nicht möglich — Startpasswort wird einmalig angezeigt.");
    }

    /**
     * Willkommens-/Reset-Mail mit zeitlich begrenztem Passwort-Setz-Link.
     * Liefert false, wenn der Versand fehlschlaegt (Fallback: Einmal-Anzeige).
     */
    private function sendWelcomeMail(User $user, bool $isReset = false): bool
    {
        try {
            $token = Password::broker()->createToken($user);
            $url = route('password.reset', ['token' => $token]).'?email='.urlencode($user->email);

            Mail::to($user->email)
                ->send(new WelcomeUserMail($user, $url, $isReset));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    public function toggleActive(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'Das eigene Konto kann nicht deaktiviert werden.');
        abort_if($user->hasRole('superadmin_demo') && ! $request->user()->hasRole('superadmin_demo'), 403);

        $user->update(['is_active' => ! $user->is_active]);

        AuditLogger::log('update', User::class, $user->id,
            ['is_active' => ! $user->is_active], ['is_active' => $user->is_active],
            $user->is_active ? 'Konto reaktiviert' : 'Konto deaktiviert');

        return back()->with('status', $user->is_active
            ? "Konto {$user->email} reaktiviert."
            : "Konto {$user->email} deaktiviert.");
    }

    public function resetPassword(Request $request, User $user)
    {
        abort_if($user->hasRole('superadmin_demo') && ! $request->user()->hasRole('superadmin_demo'), 403);

        $initialPassword = Str::password(16);
        $user->update(['password' => Hash::make($initialPassword)]);

        AuditLogger::log('update', User::class, $user->id, [], [], 'Passwort durch Administrator zurueckgesetzt');

        if ($this->sendWelcomeMail($user, isReset: true)) {
            return back()->with('status', "Passwort-Setz-Link per E-Mail an {$user->email} versendet.");
        }

        return back()
            ->with('created_user', ['email' => $user->email, 'password' => $initialPassword])
            ->with('status', "E-Mail-Versand nicht möglich — neues Startpasswort für {$user->email} wird einmalig angezeigt.");
    }
}
