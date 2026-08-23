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
        $organization->load('contacts', 'beneficialOwners', 'contracts', 'creditLines', 'kycCases', 'receivables');

        return view('organizations.show', compact('organization'));
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
