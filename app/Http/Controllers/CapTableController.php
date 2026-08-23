<?php

namespace App\Http\Controllers;

use App\Models\CapTableScenario;
use App\Models\EquityInstrument;
use App\Models\OutsourcingRegistration;
use App\Models\RelatedParty;
use App\Models\Shareholder;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\Request;

/**
 * Streng geschuetztes optionales Modul (Abschnitt 14.1/19): Cap-Table-Szenarien,
 * Related-Party-Register und Auslagerungsregister. Zugriff ist auf Geschaeftsleitung/
 * Superadmin beschraenkt (siehe routes/aurevia.php), Inhalte sind Hypothese/Entwurf.
 */
class CapTableController extends Controller
{
    public function index()
    {
        $shareholders = Shareholder::with('equityInstruments.scenario')->get();
        $scenarios = CapTableScenario::all();
        $relatedParties = RelatedParty::all();
        $outsourcing = OutsourcingRegistration::all();

        return view('captable.index', compact('shareholders', 'scenarios', 'relatedParties', 'outsourcing'));
    }

    public function storeShareholder(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:person,gesellschaft',
        ]);

        $shareholder = Shareholder::create($data + ['tenant_id' => TenantContext::id()]);
        AuditLogger::log('create', Shareholder::class, $shareholder->id, [], $shareholder->toArray());

        return back()->with('status', 'Gesellschafter angelegt.');
    }

    public function storeEquityInstrument(Request $request)
    {
        $data = $request->validate([
            'shareholder_id' => 'required|exists:shareholders,id',
            'cap_table_scenario_id' => 'nullable|exists:cap_table_scenarios,id',
            'instrument_type' => 'required|in:stammkapital,anteile,wandeldarlehen,virtuelle_beteiligung',
            'nominal_amount' => 'nullable|numeric|min:0',
            'percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $instrument = EquityInstrument::create($data + [
            'tenant_id' => TenantContext::id(),
            'valid_from' => now()->toDateString(),
            'status' => 'Hypothese',
        ]);
        AuditLogger::log('create', EquityInstrument::class, $instrument->id, [], $instrument->toArray());

        return back()->with('status', 'Beteiligungsinstrument angelegt (Hypothese).');
    }

    public function storeRelatedParty(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'relation_type' => 'required|in:organ,gesellschafter,angehoeriger,sonstige_nahestehende_person',
            'description' => 'nullable|string|max:500',
        ]);

        $party = RelatedParty::create($data + ['tenant_id' => TenantContext::id()]);
        AuditLogger::log('create', RelatedParty::class, $party->id, [], $party->toArray());

        return back()->with('status', 'Related Party angelegt.');
    }

    public function storeOutsourcing(Request $request)
    {
        $data = $request->validate([
            'service' => 'required|string|max:255',
            'provider' => 'required|string|max:255',
            'data_access' => 'required|in:keine,personenbezogen,finanzdaten,gesundheitsdaten',
            'criticality' => 'required|in:niedrig,mittel,hoch',
            'dora_relevant' => 'nullable|boolean',
        ]);

        $entry = OutsourcingRegistration::create($data + ['tenant_id' => TenantContext::id()]);
        AuditLogger::log('create', OutsourcingRegistration::class, $entry->id, [], $entry->toArray());

        return back()->with('status', 'Auslagerung registriert.');
    }
}
