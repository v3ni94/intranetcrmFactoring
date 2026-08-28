<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Support\AuditLogger;
use App\Support\RoleCatalog;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Kleines Support-Ticketsystem (v3.00): Kunden und Investoren stellen Fragen,
 * Probleme oder Wuensche ein; interne Rollen bearbeiten und antworten.
 * Externe sehen ausschliesslich ihre eigenen Tickets und keine internen Notizen.
 */
class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isInternal = $user->hasAnyRole(RoleCatalog::INTERNAL_ROLES);

        $tickets = Ticket::with('creator', 'assignee', 'organization')
            ->when(! $isInternal, fn ($q) => $q->where('created_by', $user->id))
            ->orderByRaw("CASE status WHEN 'offen' THEN 0 WHEN 'in_bearbeitung' THEN 1 WHEN 'beantwortet' THEN 2 ELSE 3 END")
            ->latest('id')
            ->paginate(20);

        return view('tickets.index', compact('tickets', 'isInternal'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|in:frage,problem,wunsch,kunde,investor,sonstiges',
            'priority' => 'nullable|in:niedrig,normal,hoch',
            'body' => 'required|string|max:5000',
        ]);

        $ticket = Ticket::create([
            'tenant_id' => TenantContext::id(),
            'ticket_number' => 'TIC-'.now()->format('y').'-'.strtoupper(Str::random(5)),
            'subject' => $data['subject'],
            'category' => $data['category'],
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'offen',
            'created_by' => $request->user()->id,
            'organization_id' => $request->user()->customer_org_id,
        ]);

        $ticket->messages()->create([
            'tenant_id' => TenantContext::id(),
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        AuditLogger::log('create', Ticket::class, $ticket->id, [], ['subject' => $ticket->subject]);

        return redirect()->route('tickets.show', $ticket)
            ->with('status', "Ticket {$ticket->ticket_number} erstellt.");
    }

    public function show(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        $isInternal = $user->hasAnyRole(RoleCatalog::INTERNAL_ROLES);

        abort_unless($isInternal || $ticket->created_by === $user->id, 403);

        $ticket->load(['creator', 'assignee', 'organization',
            'messages' => function ($q) use ($isInternal) {
                $q->when(! $isInternal, fn ($qq) => $qq->where('is_internal_note', false))->with('user')->orderBy('id');
            },
        ]);

        return view('tickets.show', compact('ticket', 'isInternal'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        $isInternal = $user->hasAnyRole(RoleCatalog::INTERNAL_ROLES);

        abort_unless($isInternal || $ticket->created_by === $user->id, 403);
        abort_if($ticket->status === 'geschlossen', 422, 'Ticket ist geschlossen.');

        $data = $request->validate([
            'body' => 'required|string|max:5000',
            'is_internal_note' => 'nullable|boolean',
        ]);

        $ticket->messages()->create([
            'tenant_id' => TenantContext::id(),
            'user_id' => $user->id,
            'body' => $data['body'],
            'is_internal_note' => $isInternal && $request->boolean('is_internal_note'),
        ]);

        // Antwortet ein interner Bearbeiter (keine interne Notiz), gilt das Ticket
        // als beantwortet; antwortet der Ersteller, geht es zurueck in Bearbeitung.
        if ($isInternal && ! $request->boolean('is_internal_note')) {
            $ticket->update(['status' => 'beantwortet', 'assigned_to' => $ticket->assigned_to ?? $user->id]);
        } elseif (! $isInternal) {
            $ticket->update(['status' => 'in_bearbeitung']);
        }

        return back()->with('status', 'Antwort gespeichert.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        abort_unless($request->user()->hasAnyRole(RoleCatalog::INTERNAL_ROLES), 403);

        $data = $request->validate([
            'status' => 'required|in:offen,in_bearbeitung,beantwortet,geschlossen',
        ]);

        $old = $ticket->status;
        $ticket->update(['status' => $data['status'], 'assigned_to' => $ticket->assigned_to ?? $request->user()->id]);

        AuditLogger::log('update', Ticket::class, $ticket->id, ['status' => $old], ['status' => $data['status']]);

        return back()->with('status', "Ticketstatus: {$data['status']}.");
    }
}
