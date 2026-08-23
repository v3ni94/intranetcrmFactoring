<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Receivable;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerReceivableController extends Controller
{
    public function index(Request $request)
    {
        $orgId = $request->user()->customer_org_id;
        $receivables = Receivable::where('organization_id', $orgId)->latest('id')->paginate(20);

        return view('customer.receivables.index', compact('receivables'));
    }

    public function create(Request $request)
    {
        $orgId = $request->user()->customer_org_id;
        $contracts = Contract::where('organization_id', $orgId)->where('status', 'aktiv')->get();

        return view('customer.receivables.create', compact('contracts'));
    }

    public function preview(Request $request)
    {
        $data = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'invoice_number' => 'required|string|max:100',
            'invoice_date' => 'required|date',
            'invoice_amount' => 'required|numeric|min:0.01',
        ]);

        $contract = Contract::findOrFail($data['contract_id']);
        $nominal = (float) $data['invoice_amount'];
        $advanceRate = (float) $contract->advance_rate_percent;
        $immediatePayout = round($nominal * $advanceRate / 100, 2);
        $reserve = round($nominal - $immediatePayout, 2);
        $fee = round($nominal * (float) $contract->factoring_fee_percent / 100, 2);

        return back()->with('preview', [
            'contract' => $contract,
            'data' => $data,
            'nominal' => $nominal,
            'advance_rate' => $advanceRate,
            'immediate_payout' => $immediatePayout - $fee,
            'reserve' => $reserve,
            'fee' => $fee,
        ])->withInput();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'invoice_number' => 'required|string|max:100',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'invoice_amount' => 'required|numeric|min:0.01',
            'debtor_pseudonym_id' => 'nullable|string|max:100',
        ]);

        $contract = Contract::findOrFail($data['contract_id']);
        $orgId = $request->user()->customer_org_id;

        $receivable = Receivable::create([
            'tenant_id' => TenantContext::id(),
            'receivable_number' => 'FRD-'.now()->format('y').'-'.strtoupper(Str::random(6)),
            'organization_id' => $orgId,
            'contract_id' => $contract->id,
            'debtor_pseudonym_id' => $data['debtor_pseudonym_id'] ?? null,
            'invoice_number' => $data['invoice_number'],
            'invoice_date' => $data['invoice_date'],
            'due_date' => $data['due_date'],
            'invoice_amount' => $data['invoice_amount'],
            'status' => 'eingereicht',
            'source_channel' => 'manuell',
            'submitted_by' => $request->user()->id,
        ]);

        AuditLogger::log('create', Receivable::class, $receivable->id, [], $receivable->toArray());

        return redirect()->route('customer.receivables.show', $receivable)
            ->with('status', 'Forderung eingereicht. Sie wird nun von Aurevia geprüft.');
    }

    public function show(Request $request, Receivable $receivable)
    {
        abort_unless($receivable->organization_id === $request->user()->customer_org_id, 403);
        $receivable->load('purchase', 'payments', 'dunningCases');

        return view('customer.receivables.show', compact('receivable'));
    }
}
