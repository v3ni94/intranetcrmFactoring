<?php

namespace Tests\Feature;

use App\Models\DemoResetLog;
use App\Models\Receivable;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DemoResetService;
use Database\Seeders\AureviaDemoDataSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DemoResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(DemoUserSeeder::class);
        $this->seed(AureviaDemoDataSeeder::class);
    }

    /**
     * Abnahmekriterium 9: Testdaten koennen sicher zurueckgesetzt werden, nur im
     * Demo-Mandanten, mit Protokollierung im DemoResetLog.
     */
    public function test_superadmin_can_reset_demo_data(): void
    {
        $this->assertGreaterThanOrEqual(250, Receivable::count());

        $superadmin = User::where('email', 'demo.superadmin_demo@aurevia-factoring.de')->firstOrFail();

        $this->actingAs($superadmin)->post(route('demo.reset'))->assertRedirect(route('demo.index'));

        $this->assertGreaterThanOrEqual(250, Receivable::count());
        $this->assertSame(1, DemoResetLog::where('action', 'reset')->count());
    }

    public function test_non_superadmin_cannot_access_demo_control(): void
    {
        $operations = User::where('email', 'demo.operations@aurevia-factoring.de')->firstOrFail();

        $this->actingAs($operations)->get(route('demo.index'))->assertForbidden();
    }

    public function test_production_tenant_is_excluded_from_wipe(): void
    {
        $productionTenant = Tenant::create(['name' => 'Produktiv', 'slug' => 'produktiv', 'type' => 'production', 'is_demo' => false]);

        $this->expectException(HttpException::class);
        app(DemoResetService::class)->wipe($productionTenant);
    }
}
