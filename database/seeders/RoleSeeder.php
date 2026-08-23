<?php

namespace Database\Seeders;

use App\Support\RoleCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (array_keys(RoleCatalog::ROLES) as $slug) {
            Role::findOrCreate($slug, 'web');
        }
    }
}
