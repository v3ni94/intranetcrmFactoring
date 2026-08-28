<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Redirect-Route statt Closure, damit "php artisan route:cache" funktioniert.
Route::redirect('/', '/login');

// Sprachumschalter (DE/EN, Session-basiert, auch fuer Gaeste auf der Login-Seite)
Route::get('/sprache/{locale}', [LocaleController::class, 'switch'])
    ->whereIn('locale', ['de', 'en'])
    ->name('locale.switch');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/aurevia.php';
