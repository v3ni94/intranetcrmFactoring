<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AureviaDemoDataSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Abschnitt 14.1/19: Cap-Table-Szenarien als streng geschuetztes optionales Modul.
 */
class CapTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(DemoUserSeeder::class);
        $this->seed(AureviaDemoDataSeeder::class);
    }

    public function test_geschaeftsleitung_can_view_captable(): void
    {
        $gl = User::where('email', 'demo.geschaeftsleitung@aurevia-factoring.de')->firstOrFail();

        $response = $this->actingAs($gl)->get(route('captable.index'));

        $response->assertOk();
        $response->assertSee('Timo Müller');
    }

    public function test_other_internal_roles_cannot_view_captable(): void
    {
        $operations = User::where('email', 'demo.operations@aurevia-factoring.de')->firstOrFail();

        $this->actingAs($operations)->get(route('captable.index'))->assertForbidden();
    }

    public function test_investor_cannot_view_captable(): void
    {
        $investor = User::where('email', 'demo.investor@aurevia-factoring.de')->firstOrFail();

        $this->actingAs($investor)->get(route('captable.index'))->assertForbidden();
    }
}
