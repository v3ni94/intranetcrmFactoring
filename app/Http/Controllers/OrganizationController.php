<?php

namespace App\Http\Controllers;

use App\Models\Organization;
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
}
