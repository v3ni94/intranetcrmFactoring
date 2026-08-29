<?php

namespace App\Http\Controllers;

use App\Models\DunningCase;
use App\Models\Receivable;
use App\Services\Integrations\CollectionsAdapter;
use App\Services\JournalService;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DunningController extends Controller
{
    public function index()
    {
        $cases = DunningCase::with('receivable.organization', 'assignee')->latest('id')->paginate(20);
        $overdueReceivables = Receivable::with('organization')
            ->where('status', 'ueberfaellig')
            ->whereDoesntHave('dunningCases', fn ($q) => $q->whereIn('status', ['offen', 'in_klaerung']))
            ->orderBy('due_date')
            ->limit(100)
            ->get();

        return view('dunning.index', compact('cases', 'overdueReceivables'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'receivable_id' => 'required|exists:receivables,id',
            'case_type' => 'required|in:mahnung,streitfall,rueckgriff,ausfall',
            'reason' => 'nullable|string|max:500',
        ]);

        $receivable = Receivable::findOrFail($data['receivable_id']);

        // Nur Forderungen mit offenem Zahlungsanspruch sind mahn-/ausfallfaehig;
        // Entwuerfe oder bereits abgerechnete Forderungen nicht (illegale Transition).
        abort_unless(
            in_array($receivable->status, ['ueberfaellig', 'teilbezahlt', 'zahlung_angewiesen', 'ausgezahlt', 'streitig'], true),
            422,
            __('Für Forderungen in diesem Status kann kein Fall angelegt werden.')
        );
        abort_if(
            $receivable->dunningCases()->whereIn('status', ['offen', 'in_klaerung'])->exists(),
            422,
            __('Zu dieser Forderung existiert bereits ein offener Fall.')
        );

        // Offener Betrag = Rechnungsbetrag abzueglich bereits zugeordneter Zahlungen.
        $openAmount = round(max(0, (float) $receivable->invoice_amount - (float) $receivable->payments()->sum('amount')), 2);

        $case = DB::transaction(function () use ($request, $data, $receivable, $openAmount) {
            $case = DunningCase::create([
                'tenant_id' => TenantContext::id(),
                'receivable_id' => $receivable->id,
                'case_type' => $data['case_type'],
                'dunning_level' => 1,
                'reason' => $data['reason'] ?? null,
                'open_amount' => $openAmount,
                'assignee_id' => $request->user()->id,
                'next_action_date' => now()->addDays(7),
            ]);

            if ($data['case_type'] === 'streitfall') {
                $receivable->update(['status' => 'streitig']);
            } elseif ($data['case_type'] === 'rueckgriff') {
                $receivable->update(['status' => 'rueckgriff']);
            } elseif ($data['case_type'] === 'ausfall') {
                $receivable->update(['status' => 'ausgefallen']);

                // Ausbuchung des Verlusts im Nebenbuch (8000 an 1400), aber nur wenn
                // die Forderung angekauft wurde (nur dann steht sie auf 1400) und ein
                // offener Betrag verbleibt.
                if ($receivable->purchase && $openAmount > 0) {
                    app(JournalService::class)->post('kreditverlust', [
                        ['account' => '8000', 'debit' => $openAmount, 'organization_id' => $receivable->organization_id],
                        ['account' => '1400', 'credit' => $openAmount, 'organization_id' => $receivable->organization_id],
                    ], Receivable::class, $receivable->id, $request->user()->id);
                }
            }

            AuditLogger::log('create', DunningCase::class, $case->id, [], $case->toArray());

            return $case;
        });

        return back()->with('status', __('Fall angelegt: :type', ['type' => $data['case_type']]));
    }

    public function close(Request $request, DunningCase $case)
    {
        $case->update(['status' => 'geschlossen']);
        AuditLogger::log('update', DunningCase::class, $case->id, [], ['status' => 'geschlossen']);

        return back()->with('status', __('Fall geschlossen.'));
    }

    public function handOverToCollections(DunningCase $case, CollectionsAdapter $adapter)
    {
        $reference = $adapter->handOver($case);
        AuditLogger::log('update', DunningCase::class, $case->id, [], ['status' => 'inkasso'], 'Übergabe an Inkasso-Partner (Demo)');

        return back()->with('status', __('An Inkasso-Partner übergeben (Demo), Referenz :reference.', ['reference' => $reference]));
    }
}
