<?php

namespace App\Http\Controllers;

use App\Support\RoleCatalog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Leitet nach Login auf das zur Hauptrolle passende Dashboard weiter.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $primaryRole = $user->getRoleNames()->first();

        $route = RoleCatalog::DASHBOARD_ROUTE[$primaryRole] ?? 'dashboard.mitarbeiter';

        return redirect()->route($route);
    }
}
