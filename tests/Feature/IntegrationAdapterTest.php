<?php

namespace Tests\Feature;

use App\Models\IntegrationEvent;
use App\Models\IntegrationProvider;
use App\Models\KycCase;
use App\Models\Organization;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Abschnitt 20: Adapter statt Abhaengigkeit. Jeder Aufruf muss im Statusregister
 * und im Ereignisprotokoll nachvollziehbar sein, unabhaengig vom konkreten Anbieter.
 */
class IntegrationAdapterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(DemoUserSeeder::class);
    }

    public function test_integration_status_page_registers_all_catalog_providers(): void
    {
        $admin = User::where('email', 'demo.systemadministration@aurevia-factoring.de')->firstOrFail();

        $this->actingAs($admin)->get(route('integrations.index'))->assertOk();

        $this->assertSame(11, IntegrationProvider::count());
    }

    public function test_kyc_action_logs_integration_event_and_updates_provider_status(): void
    {
        TenantContext::set(Tenant::where('slug', 'aurevia-demo')->value('id'));

        $organization = Organization::create([
            'tenant_id' => TenantContext::id(), 'org_type' => 'customer', 'name' => 'Testpraxis',
            'customer_status' => 'Aktiv', 'risk_class' => 'niedrig', 'is_demo' => true,
        ]);

        $operations = User::where('email', 'demo.operations@aurevia-factoring.de')->firstOrFail();

        $this->actingAs($operations)
            ->post(route('organizations.run-kyc', $organization))
            ->assertRedirect();

        $this->assertSame(1, KycCase::where('organization_id', $organization->id)->count());

        $provider = IntegrationProvider::where('key', 'kyc_kyb')->firstOrFail();
        $this->assertSame('healthy', $provider->status);
        $this->assertNotNull($provider->last_success_at);

        $this->assertSame(1, IntegrationEvent::where('integration_provider_id', $provider->id)->count());
    }

    public function test_non_internal_role_cannot_reach_integration_status_page(): void
    {
        $investor = User::where('email', 'demo.investor@aurevia-factoring.de')->firstOrFail();

        $this->actingAs($investor)->get(route('integrations.index'))->assertForbidden();
    }
}
