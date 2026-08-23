<?php

namespace App\Http\Controllers;

use App\Models\Decision;
use App\Models\FinancialScenario;

class GovernanceController extends Controller
{
    public const PERSONS = [
        ['name' => 'Timo Müller', 'focus' => 'Strategie, Kapital, Unternehmensentwicklung', 'role_status' => 'Zielrolle offen'],
        ['name' => 'David Enns', 'focus' => 'Technologie/Systeme und Softwarebezug', 'role_status' => 'IP- und Auslagerungsmodell offen'],
        ['name' => 'Jürgen Brink', 'focus' => 'Finance und Regulatory', 'role_status' => 'Mögliche Geschäftsleitungsrolle offen'],
        ['name' => 'Carsten Walprecht', 'focus' => 'Markt, Finanzierung und Vertrieb', 'role_status' => 'Interessenkonfliktprüfung offen'],
        ['name' => 'Jan Walprecht', 'focus' => 'Business Development, Operations oder PMO', 'role_status' => 'Zielrolle offen'],
    ];

    public function index()
    {
        $decisions = Decision::orderByDesc('decision_date')->get();
        $scenarios = FinancialScenario::all();

        return view('governance.index', ['decisions' => $decisions, 'scenarios' => $scenarios, 'persons' => self::PERSONS]);
    }
}
