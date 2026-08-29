<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeUserMail;
use App\Models\Organization;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\RoleCatalog;
use App\Support\TenantContext;
use Illuminate\Database\QueryException;
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
            return back()->withErrors(['role' => __('Diese Rolle kann nur durch einen Superadmin vergeben werden.')])->withInput();
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
                ->with('status', __('Benutzer :email angelegt. Die Zugangsdaten (Passwort-Setz-Link) wurden per E-Mail versendet.', ['email' => $user->email]));
        }

        // Fallback bei fehlgeschlagenem Mailversand (z.B. SMTP nicht konfiguriert):
        // Startpasswort einmalig anzeigen, damit der Zugang uebergeben werden kann.
        return redirect()->route('users.index')
            ->with('created_user', ['email' => $user->email, 'password' => $initialPassword])
            ->with('status', __('Benutzer :email angelegt. E-Mail-Versand nicht möglich — Startpasswort wird einmalig angezeigt.', ['email' => $user->email]));
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

    /**
     * Personalakte / Benutzer bearbeiten (v3.02): Stammdaten, Berichtslinien,
     * Nachweise, Beschaeftigungszeitraum und Rollentausch.
     */
    public function edit(User $user)
    {
        $supervisors = User::where('id', '!=', $user->id)->orderBy('name')->get(['id', 'name']);
        $organizations = Organization::where('org_type', 'customer')->orderBy('name')->get(['id', 'name']);

        return view('users.edit', [
            'user' => $user,
            'roles' => RoleCatalog::ROLES,
            'customerRoles' => self::CUSTOMER_ROLES,
            'supervisors' => $supervisors,
            'organizations' => $organizations,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email:strict', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys(RoleCatalog::ROLES))],
            'customer_org_id' => [
                Rule::requiredIf(in_array($request->input('role'), self::CUSTOMER_ROLES, true)),
                'nullable', 'exists:organizations,id',
            ],
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'supervisor_id' => ['nullable', 'exists:users,id', Rule::notIn([$user->id])],
            'disciplinary_supervisor_id' => ['nullable', 'exists:users,id', Rule::notIn([$user->id])],
            'phone_business' => 'nullable|string|max:50',
            'phone_private' => 'nullable|string|max:50',
            'email_private' => 'nullable|email:strict|max:255',
            'street' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date|before:today',
            'tax_id' => 'nullable|string|max:20',
            'id_card_number' => 'nullable|string|max:30',
            'id_card_valid_until' => 'nullable|date',
            'criminal_record_check_at' => 'nullable|date',
            'drivers_license_class' => 'nullable|string|max:20',
            'drivers_license_valid_until' => 'nullable|date',
            'schufa_check_at' => 'nullable|date',
            'hr_notes' => 'nullable|string|max:2000',
            'joined_at' => 'nullable|date',
            'left_at' => 'nullable|date|after_or_equal:joined_at',
        ]);

        // Rollentausch: Superadmin-Vergabe/-Entzug nur durch Superadmin.
        $currentRole = $user->getRoleNames()->first();
        if ($data['role'] !== $currentRole) {
            if (($data['role'] === 'superadmin_demo' || $currentRole === 'superadmin_demo')
                && ! $request->user()->hasRole('superadmin_demo')) {
                return back()->withErrors(['role' => __('Die Superadmin-Rolle kann nur durch einen Superadmin vergeben oder entzogen werden.')])->withInput();
            }
        }

        $old = $user->only(['name', 'email', 'position', 'department', 'joined_at', 'left_at']);
        $old['role'] = $currentRole;

        $user->fill($data);
        $user->customer_org_id = in_array($data['role'], self::CUSTOMER_ROLES, true) ? $data['customer_org_id'] : null;
        $user->save();
        $user->syncRoles([$data['role']]);

        AuditLogger::log('update', User::class, $user->id, $old, [
            'role' => $data['role'], 'position' => $user->position, 'department' => $user->department,
            'joined_at' => $user->joined_at?->toDateString(), 'left_at' => $user->left_at?->toDateString(),
        ], 'Personalakte/Benutzer aktualisiert');

        return redirect()->route('users.edit', $user)->with('status', __('Benutzer aktualisiert.'));
    }

    /**
     * Loeschen ist nur ohne Historie moeglich (Fremdschluessel schuetzen den
     * Audit-Trail); ansonsten bleibt die Deaktivierung der richtige Weg.
     */
    public function destroy(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, __('Das eigene Konto kann nicht gelöscht werden.'));
        abort_if($user->hasRole('superadmin_demo') && ! $request->user()->hasRole('superadmin_demo'), 403);

        try {
            AuditLogger::log('delete', User::class, $user->id, ['email' => $user->email], [], 'Benutzer gelöscht');
            $user->syncRoles([]);
            $user->delete();
        } catch (QueryException $e) {
            return back()->withErrors([
                'delete' => __('Löschen nicht möglich: Der Benutzer hat Historie (Vorgänge, Freigaben, Tickets). Bitte stattdessen deaktivieren — so bleibt der Audit-Trail erhalten.'),
            ]);
        }

        return redirect()->route('users.index')->with('status', __('Benutzer gelöscht.'));
    }

    public function toggleActive(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, __('Das eigene Konto kann nicht deaktiviert werden.'));
        abort_if($user->hasRole('superadmin_demo') && ! $request->user()->hasRole('superadmin_demo'), 403);

        $user->update(['is_active' => ! $user->is_active]);

        AuditLogger::log('update', User::class, $user->id,
            ['is_active' => ! $user->is_active], ['is_active' => $user->is_active],
            $user->is_active ? 'Konto reaktiviert' : 'Konto deaktiviert');

        return back()->with('status', $user->is_active
            ? __('Konto :email reaktiviert.', ['email' => $user->email])
            : __('Konto :email deaktiviert.', ['email' => $user->email]));
    }

    public function resetPassword(Request $request, User $user)
    {
        abort_if($user->hasRole('superadmin_demo') && ! $request->user()->hasRole('superadmin_demo'), 403);

        $initialPassword = Str::password(16);
        $user->update(['password' => Hash::make($initialPassword)]);

        AuditLogger::log('update', User::class, $user->id, [], [], 'Passwort durch Administrator zurueckgesetzt');

        if ($this->sendWelcomeMail($user, isReset: true)) {
            return back()->with('status', __('Passwort-Setz-Link per E-Mail an :email versendet.', ['email' => $user->email]));
        }

        return back()
            ->with('created_user', ['email' => $user->email, 'password' => $initialPassword])
            ->with('status', __('E-Mail-Versand nicht möglich — neues Startpasswort für :email wird einmalig angezeigt.', ['email' => $user->email]));
    }
}
