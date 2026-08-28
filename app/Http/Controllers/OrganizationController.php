<?php

namespace App\Http\Controllers;

use App\Models\BeneficialOwner;
use App\Models\Contract;
use App\Models\Organization;
use App\Services\Integrations\CreditBureauAdapter;
use App\Services\Integrations\ESignatureAdapter;
use App\Services\Integrations\KycKybAdapter;
use App\Services\Integrations\PepSanctionsAdapter;
use App\Services\Integrations\RegisterUboAdapter;
use App\Support\AuditLogger;
use App\Support\RatingCatalog;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $organizations = Organization::customers()->with('accountManager')->orderBy('name')->paginate(25);

        return view('organizations.index', compact('organizations'));
    }

    public function debtors()
    {
        $debtors = Organization::debtors()->orderBy('name')->paginate(25);

        return view('organizations.debtors', compact('debtors'));
    }

    public function show(Organization $organization)
    {
        // Forderungshistorie begrenzen: die Detailseite zeigt die juengsten Vorgaenge,
        // nicht die komplette Historie (waechst in Produktion unbegrenzt).
        $organization->load([
            'contacts', 'beneficialOwners', 'contracts', 'creditLines', 'kycCases',
            'receivables' => fn ($q) => $q->latest('id')->limit(50),
        ]);

        return view('organizations.show', compact('organization'));
    }

    /**
     * Internes Rating setzen (Kunde ODER Investor). Punkte 0-100 werden nach
     * RatingCatalog in eine Stufe AAA..C uebersetzt; die Stufe bestimmt den
     * Gebuehrenaufschlag beim Ankauf. Aenderungen werden auditiert.
     */
    public function updateRating(Request $request, Organization $organization)
    {
        $data = $request->validate([
            'rating_points' => 'required|integer|min:0|max:100',
            'segment' => 'nullable|in:'.implode(',', array_keys(RatingCatalog::SEGMENTS)),
            'customer_type' => 'required|in:b2b,b2c',
        ]);

        $old = $organization->only(['rating', 'rating_points', 'segment', 'customer_type']);
        $grade = RatingCatalog::gradeForPoints((int) $data['rating_points']);

        $organization->update([
            'rating' => $grade,
            'rating_points' => $data['rating_points'],
            'rating_updated_at' => now(),
            'segment' => $data['segment'] ?? $organization->segment,
            'customer_type' => $data['customer_type'],
        ]);

        AuditLogger::log('update', Organization::class, $organization->id, $old, [
            'rating' => $grade, 'rating_points' => $data['rating_points'],
            'segment' => $organization->segment, 'customer_type' => $organization->customer_type,
        ], 'Rating/Segment aktualisiert');

        return back()->with('status', "Rating aktualisiert: {$grade} ({$data['rating_points']} Punkte).");
    }

    public function runKyc(Request $request, Organization $organization, KycKybAdapter $adapter)
    {
        $adapter->screen($organization, $request->user()->id);

        return back()->with('status', 'KYC/KYB-Prüfung (Sandbox) durchgeführt.');
    }

    public function runCreditCheck(Organization $organization, CreditBureauAdapter $adapter)
    {
        $result = $adapter->score($organization);

        return back()->with('status', "Bonitätsauskunft (Sandbox): Score {$result['score']}, Rating {$result['rating']}.");
    }

    public function runRegisterCheck(Organization $organization, RegisterUboAdapter $adapter)
    {
        $adapter->verify($organization);

        return back()->with('status', 'Registerabgleich (Sandbox) ohne Beanstandung.');
    }

    public function runPepScreening(BeneficialOwner $owner, PepSanctionsAdapter $adapter)
    {
        $adapter->screen($owner);

        return back()->with('status', 'PEP-/Sanktionsscreening (Sandbox) durchgeführt.');
    }

    public function signContract(Request $request, Contract $contract, ESignatureAdapter $adapter)
    {
        $adapter->sign($contract, $request->user()->id);

        return back()->with('status', 'Vertrag digital signiert (Demo-Signatur, kein Rechtsverkehr).');
    }
}
