<?php

namespace App\Providers;

use App\Models\User;
use App\Support\RoleCatalog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('upload-documents', function (User $user) {
            return $user->hasAnyRole(RoleCatalog::INTERNAL_ROLES);
        });
    }
}
