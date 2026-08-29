<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\RoleCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Vorschau-Modus (v3.05): Administration und Geschaeftsleitung koennen die
 * Anwendung aus Sicht jeder anderen Rolle betrachten (Kunde, Investor, HR usw.).
 * Technisch wird auf ein eigens angelegtes, als Testdaten markiertes
 * Vorschau-Konto der Zielrolle gewechselt; die eigene Konto-ID bleibt in der
 * Session, sodass der Rueckweg jederzeit moeglich ist. Rollen mit
 * Organisationsbindung (Kunde, Investor) werden an eine Testdaten-Organisation
 * gebunden und zeigen damit Beispieldaten.
 */
class PreviewModeController extends Controller
{
    public const SESSION_KEY = 'vorschau_admin_id';

    /** Rollen mit Organisationsbindung => Organisationstyp der Beispieldaten. */
    private const ORG_TYPE_BY_ROLE = [
        'kunde_admin' => 'customer',
        'kunde_sachbearbeitung' => 'customer',
        'investor' => 'investor',
    ];

    public function start(Request $request, string $role)
    {
        abort_if($request->session()->has(self::SESSION_KEY), 422, __('Vorschau-Modus läuft bereits. Bitte zuerst beenden.'));
        abort_unless(array_key_exists($role, RoleCatalog::ROLES), 404);
        abort_if($role === 'superadmin_demo', 403, __('Für die Superadmin-Rolle gibt es keine Vorschau.'));

        $admin = $request->user();

        // Rollen mit Organisationsbindung brauchen eine Testdaten-Organisation
        $organization = null;
        if (isset(self::ORG_TYPE_BY_ROLE[$role])) {
            $organization = Organization::where('org_type', self::ORG_TYPE_BY_ROLE[$role])
                ->where('is_demo', true)->orderBy('id')->first();

            if (! $organization) {
                return back()->with('error', __('Für diese Vorschau werden Beispieldaten benötigt. Bitte zuerst in der Demo-Steuerung „Testdaten einspielen“ ausführen.'));
            }
        }

        // Vorschau-Konto der Zielrolle anlegen bzw. aktualisieren (als Testdaten markiert)
        $preview = User::updateOrCreate(
            ['email' => 'vorschau.'.$role.'@aurevia-vorschau.local'],
            [
                'name' => __('Vorschau: :role', ['role' => RoleCatalog::label($role)]),
                'tenant_id' => $admin->tenant_id,
                'password' => Hash::make(Str::password(32)),
                'customer_org_id' => $organization?->id,
            ]
        );
        $preview->forceFill(['is_demo' => true, 'is_active' => true])->save();
        $preview->syncRoles([$role]);

        AuditLogger::log('update', User::class, $preview->id, [], [
            'vorschau_rolle' => $role, 'gestartet_von' => $admin->email,
        ], 'Vorschau-Modus gestartet');

        $request->session()->put(self::SESSION_KEY, $admin->id);
        Auth::login($preview);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', __('Vorschau-Modus aktiv: Sie sehen die Anwendung jetzt als :role.', ['role' => __(RoleCatalog::label($role))]));
    }

    public function stop(Request $request)
    {
        $adminId = $request->session()->get(self::SESSION_KEY);
        abort_unless($adminId, 404);

        $admin = User::find($adminId);
        abort_unless($admin && $admin->is_active, 403, __('Ursprüngliches Konto nicht mehr verfügbar. Bitte ab- und neu anmelden.'));

        AuditLogger::log('update', User::class, $admin->id, [], [
            'vorschau_rolle' => $request->user()?->getRoleNames()->first(),
        ], 'Vorschau-Modus beendet');

        $request->session()->forget(self::SESSION_KEY);
        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', __('Vorschau-Modus beendet.'));
    }
}
