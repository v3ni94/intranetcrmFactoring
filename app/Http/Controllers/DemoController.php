<?php

namespace App\Http\Controllers;

use App\Models\DemoResetLog;
use App\Models\Tenant;
use App\Services\DemoResetService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DemoController extends Controller
{
    public function index(Request $request, DemoResetService $service)
    {
        $tenant = $request->user()->tenant;
        $recordCount = $tenant ? $service->countDemoRecords($tenant) : 0;
        $logs = DemoResetLog::with('performer')->latest('performed_at')->limit(10)->get();

        return view('demo.index', compact('tenant', 'recordCount', 'logs'));
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

        return redirect()->route('demo.index')->with('status', "Demo zurückgesetzt. {$affected} Datensätze ersetzt durch die definierte Ausgangslage.");
    }

    public function delete(Request $request, DemoResetService $service)
    {
        $data = $request->validate([
            'confirmation' => 'required|string',
            'password' => 'required|string',
        ]);

        abort_unless($data['confirmation'] === 'DEMO LÖSCHEN', 422, 'Bitte exakt "DEMO LÖSCHEN" eingeben, um fortzufahren.');
        abort_unless(Hash::check($data['password'], $request->user()->password), 403, 'Passwort zur erneuten Authentifizierung stimmt nicht.');

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

        return redirect()->route('demo.index')->with('status', "Alle Demo-Daten gelöscht ({$affected} Datensätze). Nutzer, Rollen und Tenant bleiben erhalten.");
    }
}
