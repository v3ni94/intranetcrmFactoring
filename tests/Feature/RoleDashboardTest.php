<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\RoleCatalog;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(DemoUserSeeder::class);
    }

    /**
     * Jede Rolle muss nach Login auf ihr passendes, funktionierendes Dashboard gelangen
     * (Abnahmekriterium 1: unterschiedliche, passende Dashboards je Rolle).
     */
    public function test_every_role_reaches_its_dashboard(): void
    {
        foreach (RoleCatalog::ROLES as $slug => $label) {
            $user = User::where('email', "demo.{$slug}@aurevia-factoring.de")->firstOrFail();

            $response = $this->actingAs($user)->get('/dashboard');
            $response->assertRedirect(route(RoleCatalog::DASHBOARD_ROUTE[$slug]));

            $dashboard = $this->actingAs($user)->get(route(RoleCatalog::DASHBOARD_ROUTE[$slug]));
            $dashboard->assertOk();
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
