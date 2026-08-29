<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Document;
use App\Models\Facility;
use App\Models\Organization;
use App\Services\ContractTemplateService;
use App\Support\AuditLogger;
use App\Support\RoleCatalog;
use Illuminate\Http\Request;

/**
 * Mustervertraege (v3.03): Erzeugen aus Systemdaten, einfache elektronische
 * Signatur beider Seiten, automatische Freigabe an die Gegenseite.
 */
class ContractDocumentController extends Controller
{
    public function generateCustomer(Request $request, Contract $contract, ContractTemplateService $service)
    {
        $document = $service->buildCustomerContract($contract, $request->user()->id);

        // Freigabe an die eigene Kundenorganisation, damit der Kunde signieren kann.
        $document->update([
            'visibility' => 'extern_freigegeben',
            'release_purpose' => 'vertrag',
            'released_by' => $request->user()->id,
        ]);

        AuditLogger::log('create', Document::class, $document->id,
            [], ['contract' => $contract->contract_number], 'Mustervertrag Kunde erzeugt');

        return redirect()->route('documents.index')
            ->with('status', __('Mustervertrag :number erzeugt und zur Unterzeichnung bereitgestellt.', ['number' => $contract->contract_number]));
    }

    public function generateInvestor(Request $request, Facility $facility, ContractTemplateService $service)
    {
        $document = $service->buildInvestorContract($facility, $request->user()->id);

        $document->update([
            'visibility' => 'extern_freigegeben',
            'release_purpose' => 'vertrag',
            'release_audience' => 'investor',
            'released_by' => $request->user()->id,
        ]);

        AuditLogger::log('create', Document::class, $document->id,
            [], ['facility' => $facility->facility_number], 'Mustervertrag Investor erzeugt');

        return redirect()->route('documents.index')
            ->with('status', __('Fazilitätsvertrag :number erzeugt und zur Unterzeichnung bereitgestellt.', ['number' => $facility->facility_number]));
    }

    public function sign(Request $request, Document $document, ContractTemplateService $service)
    {
        abort_unless($document->category === 'vertrag', 404);

        $data = $request->validate([
            'side' => 'required|in:company,counterparty',
            'signer_name' => 'required|string|max:120',
            'confirm' => 'accepted',
        ]);

        $user = $request->user();
        $isInternal = $user->hasAnyRole(RoleCatalog::INTERNAL_ROLES);

        if ($data['side'] === 'company') {
            // Fuer die Gesellschaft zeichnen nur Geschaeftsleitung/Superadmin.
            abort_unless($user->hasAnyRole(['geschaeftsleitung', 'superadmin_demo']), 403);
            abort_if($document->signed_company_at !== null, 422, __('Dieses Dokument ist für die Gesellschaft bereits unterzeichnet.'));

            $document->update([
                'signed_company_name' => $data['signer_name'],
                'signed_company_at' => now(),
                'signed_company_by' => $user->id,
            ]);
        } else {
            // Gegenseite: der gebundene externe Nutzer der Organisation ODER
            // intern (Geschaeftsleitung) zur Erfassung einer schriftlich
            // vorliegenden Zustimmung.
            $ownOrg = $document->related_type === Organization::class
                && (int) $document->related_id === (int) ($user->customer_org_id ?? 0);

            abort_unless($ownOrg || $user->hasAnyRole(['geschaeftsleitung', 'superadmin_demo']), 403);
            // Externe duerfen nur Dokumente signieren, die ihnen auch freigegeben
            // wurden — interne Entwuerfe sind fuer sie weder sichtbar noch zeichenbar.
            abort_unless($isInternal || $document->isExternallyReleased(), 403);
            abort_if($document->signed_counterparty_at !== null, 422, __('Dieses Dokument ist von der Gegenseite bereits unterzeichnet.'));

            $document->update([
                'signed_counterparty_name' => $data['signer_name'],
                'signed_counterparty_at' => now(),
                'signed_counterparty_by' => $user->id,
            ]);
        }

        // PDF mit aktualisiertem Signaturblock neu rendern
        $service->refresh($document->refresh(), $document->owner_id ?? $user->id);

        AuditLogger::log('update', Document::class, $document->id,
            [], ['side' => $data['side'], 'signer' => $data['signer_name']], 'Vertrag elektronisch signiert');

        return back()->with('status', $document->refresh()->isFullySigned()
            ? __('Vertrag vollständig unterzeichnet und hinterlegt.')
            : __('Signatur erfasst. Es fehlt noch die Unterschrift der anderen Seite.'));
    }
}
