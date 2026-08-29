<?php

namespace App\Http\Controllers;

use App\Models\DemoResetLog;
use App\Models\Tenant;
use App\Services\DemoResetService;
use App\Services\ShowcaseDataService;
use App\Support\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DemoController extends Controller
{
    public function index(Request $request, DemoResetService $service, ShowcaseDataService $showcase)
    {
        $tenant = $request->user()->tenant;
        $recordCount = $tenant ? $service->countDemoRecords($tenant) : 0;
        $logs = DemoResetLog::with('performer')->latest('performed_at')->limit(10)->get();

        return view('demo.index', [
            'tenant' => $tenant,
            'recordCount' => $recordCount,
            'logs' => $logs,
            'hasShowcase' => $tenant ? $showcase->hasShowcaseData($tenant) : false,
            'showcaseCount' => $tenant ? $showcase->countTestRecords($tenant) : 0,
            'allCount' => $tenant ? $showcase->countAllRecords($tenant) : 0,
        ]);
    }

    /**
     * v3.03: Vorfuehr-Testdaten auf dem aktuellen Mandanten einspielen
     * (100 fiktive Medizin-Kunden, Investoren mit Ausschuettungshistorie,
     * Kosten, Mustervertraege). Idempotent.
     */
    public function seedShowcase(Request $request, ShowcaseDataService $showcase)
    {
        $tenant = $request->user()->tenant;

        if ($showcase->hasShowcaseData($tenant)) {
            return back()->with('error', __('Es sind bereits Testdaten vorhanden. Bitte zuerst löschen, dann neu einspielen.'));
        }

        $created = $showcase->seed($tenant, $request->user());

        AuditLogger::log('create', Tenant::class, $tenant->id, [], ['records' => $created], 'Testdaten eingespielt');

        return back()->with('status', __(':count Testdatensätze eingespielt (Kunden, Investoren, Verträge, Forderungen, Ausschüttungen, Kosten).', ['count' => $created]));
    }

    /**
     * v3.03: Loescht ausschliesslich als Testdaten markierte Datensaetze.
     * Erfordert die erneute Passworteingabe.
     */
    public function purgeShowcase(Request $request, ShowcaseDataService $showcase)
    {
        $data = $request->validate(['password' => 'required|string']);
        abort_unless(Hash::check($data['password'], $request->user()->password), 403, __('Passwort zur erneuten Authentifizierung stimmt nicht.'));

        $tenant = $request->user()->tenant;

        try {
            $affected = $showcase->purgeTestData($tenant);
        } catch (QueryException $e) {
            // Fremdschluessel-Konflikt: eigene (nicht als Testdaten markierte)
            // Datensaetze haengen an Testdaten-Eltern. Klare Meldung statt Fehler 500.
            report($e);

            return back()->with('error', __('Löschen nicht möglich: Es existieren eigene Datensätze, die mit Testdaten verknüpft sind (z. B. Forderungen zu einem Testkunden). Bitte diese zuerst entfernen oder die vollständige Löschung verwenden.'));
        }

        DemoResetLog::create([
            'tenant_id' => $tenant->id,
            'action' => 'delete',
            'performed_by' => $request->user()->id,
            'affected_records' => $affected,
            'performed_at' => now(),
        ]);

        AuditLogger::log('delete', Tenant::class, $tenant->id, [], ['records' => $affected], 'Testdaten gelöscht');

        return back()->with('status', __('Testdaten endgültig gelöscht (:count Datensätze). Eigene, nicht als Testdaten markierte Daten blieben erhalten.', ['count' => $affected]));
    }

    /**
     * v3.03: Loescht ALLE Bewegungs- und Stammdaten des Mandanten, auch selbst
     * angelegte. Nutzer, Rollen und Mandant bleiben erhalten. Erfordert Passwort
     * UND Bestaetigungsphrase; der Vorgang ist endgueltig und unwiderruflich.
     */
    public function purgeAll(Request $request, ShowcaseDataService $showcase)
    {
        $data = $request->validate([
            'confirmation' => 'required|string',
            'password' => 'required|string',
        ]);

        abort_unless($data['confirmation'] === 'ALLES LÖSCHEN', 422, __('Bitte exakt "ALLES LÖSCHEN" eingeben, um fortzufahren.'));
        abort_unless(Hash::check($data['password'], $request->user()->password), 403, __('Passwort zur erneuten Authentifizierung stimmt nicht.'));

        $tenant = $request->user()->tenant;
        $affected = $showcase->purgeAll($tenant);

        DemoResetLog::create([
            'tenant_id' => $tenant->id,
            'action' => 'delete',
            'performed_by' => $request->user()->id,
            'affected_records' => $affected,
            'performed_at' => now(),
        ]);

        AuditLogger::log('delete', Tenant::class, $tenant->id, [], ['records' => $affected], 'Alle Daten des Mandanten gelöscht');

        return back()->with('status', __('Alle Daten endgültig und unwiderruflich gelöscht (:count Datensätze). Nutzer, Rollen und Mandant bleiben erhalten.', ['count' => $affected]));
    }

    public function reset(Request $request, DemoResetService $service)
    {
        $tenant = $request->user()->tenant;
        $service->assertDemoTenant($tenant);

        $affected = $service->wipe($tenant);
        $service->reseed($tenant);

        DemoResetLog::create([
            'tenant_id' => $tenant->id,
            'action' => 'reset',
            'performed_by' => $request->user()->id,
            'affected_records' => $affected,
            'performed_at' => now(),
        ]);

        AuditLogger::log('delete', Tenant::class, $tenant->id, [], ['affected' => $affected], 'Demo zurückgesetzt');

        return redirect()->route('demo.index')->with('status', __('Demo zurückgesetzt. :affected Datensätze ersetzt durch die definierte Ausgangslage.', ['affected' => $affected]));
    }

    public function delete(Request $request, DemoResetService $service)
    {
        $data = $request->validate([
            'confirmation' => 'required|string',
            'password' => 'required|string',
        ]);

        abort_unless($data['confirmation'] === 'DEMO LÖSCHEN', 422, __('Bitte exakt "DEMO LÖSCHEN" eingeben, um fortzufahren.'));
        abort_unless(Hash::check($data['password'], $request->user()->password), 403, __('Passwort zur erneuten Authentifizierung stimmt nicht.'));

        $tenant = $request->user()->tenant;
        $service->assertDemoTenant($tenant);

        $affected = $service->wipe($tenant);

        DemoResetLog::create([
            'tenant_id' => $tenant->id,
            'action' => 'delete',
            'performed_by' => $request->user()->id,
            'affected_records' => $affected,
            'performed_at' => now(),
        ]);

        AuditLogger::log('delete', Tenant::class, $tenant->id, [], ['affected' => $affected], 'Alle Demo-Daten gelöscht');

        return redirect()->route('demo.index')->with('status', __('Alle Demo-Daten gelöscht (:affected Datensätze). Nutzer, Rollen und Tenant bleiben erhalten.', ['affected' => $affected]));
    }
}
