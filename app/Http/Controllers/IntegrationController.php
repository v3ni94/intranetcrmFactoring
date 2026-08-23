<?php

namespace App\Http\Controllers;

use App\Models\IntegrationEvent;
use App\Models\IntegrationProvider;
use App\Services\Integrations\IntegrationCatalog;
use App\Support\TenantContext;

class IntegrationController extends Controller
{
    public function index()
    {
        foreach (IntegrationCatalog::PROVIDERS as $key => $meta) {
            IntegrationProvider::firstOrCreate(
                ['tenant_id' => TenantContext::id(), 'key' => $key],
                ['category' => $meta['category'], 'name' => $meta['name'], 'mode' => 'sandbox', 'status' => 'unbekannt']
            );
        }

        $providers = IntegrationProvider::withCount('events')->orderBy('category')->get();
        $recentEvents = IntegrationEvent::with('provider')->latest('id')->limit(20)->get();

        return view('integrations.index', compact('providers', 'recentEvents'));
    }
}
