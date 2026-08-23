<?php

namespace App\Http\Controllers;

use App\Models\Lead;

class OnboardingController extends Controller
{
    public function index()
    {
        $stages = Lead::STATUSES;
        $leadsByStage = Lead::with('organization')->get()->groupBy('status');

        return view('onboarding.index', compact('stages', 'leadsByStage'));
    }
}
