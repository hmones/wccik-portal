<?php

use App\Http\Controllers\Public\ApplicationController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

RateLimiter::for('application.submit', function (Request $request) {
    return Limit::perHour(5)->by($request->ip());
});

Route::inertia('/', 'Welcome')->name('home');

Route::post('/locale/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['en', 'ur']), 404);
    session(['locale' => $locale]);

    return redirect()->back();
})->name('locale.switch');

Route::get('/apply', [ApplicationController::class, 'create'])->name('apply');
Route::post('/apply', [ApplicationController::class, 'store'])->middleware('throttle:application.submit')->name('apply.store');
Route::get('/apply/confirmation/{token}', [ApplicationController::class, 'confirmation'])->name('apply.confirmation');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
