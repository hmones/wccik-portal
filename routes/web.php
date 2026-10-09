<?php

use App\Http\Controllers\Admin\ApplicationPdfController as AdminApplicationPdfController;
use App\Http\Controllers\Public\ApplicationController;
use App\Http\Controllers\Public\ApplicationPdfController;
use App\Http\Controllers\Public\PaymentProofController;
use App\Http\Controllers\Public\PortalApplicationController;
use App\Http\Controllers\Public\PortalAuthController;
use App\Http\Controllers\Public\PortalDashboardController;
use App\Http\Controllers\Public\PortalRenewalController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

RateLimiter::for('application.submit', function (Request $request) {
    return Limit::perHour(5)->by($request->ip());
});

RateLimiter::for('portal.otp', function (Request $request) {
    return [
        Limit::perHour(3)->by($request->ip().'|'.mb_strtolower((string) $request->input('email', ''))),
        Limit::perHour(15)->by($request->ip()),
    ];
});

Route::inertia('/', 'Welcome')->name('home');

Route::get('/robots.txt', function (): Response {
    $sitemap = url('/sitemap.xml');

    return response(<<<ROBOTS
# WCCIK Membership Portal

User-agent: *
Allow: /
Disallow: /portal/
Disallow: /admin/
Disallow: /nova
Disallow: /nova/
Disallow: /storage/

Sitemap: {$sitemap}
ROBOTS, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
})->name('robots');

Route::get('/sitemap.xml', function (): Response {
    $home = url('/');
    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
    <url>
        <loc>{$home}/</loc>
        <xhtml:link rel="alternate" hreflang="en-PK" href="{$home}/" />
        <xhtml:link rel="alternate" hreflang="ur-PK" href="{$home}/" />
        <xhtml:link rel="alternate" hreflang="x-default" href="{$home}/" />
        <changefreq>monthly</changefreq>
        <priority>1.0</priority>
    </url>
</urlset>
XML;

    return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
})->name('sitemap');

Route::post('/locale/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['en', 'ur']), 404);
    session(['locale' => $locale]);

    return redirect()->back();
})->name('locale.switch');

// Legacy public /apply flow, superseded by the applicant portal
// (/portal/apply). Kept callable until a cleanup pass removes it so old
// bookmarks and the existing feature tests keep working.
Route::get('/apply', [ApplicationController::class, 'create'])->name('apply');
Route::post('/apply', [ApplicationController::class, 'store'])->middleware('throttle:application.submit')->name('apply.store');
Route::get('/apply/confirmation/{token}', [ApplicationController::class, 'confirmation'])->name('apply.confirmation');

// Renewal is initiated from inside the applicant portal (dashboard button
// when the linked membership is expired). Any old /renew* bookmark lands
// at the portal where the right action is shown.
Route::get('/renew', fn () => redirect()->route('portal.home'))->name('renew');
Route::get('/renew/confirmation/{token}', fn () => redirect()->route('portal.home'))->name('renew.confirmation');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('/admin/applications/{application}/pdf', AdminApplicationPdfController::class)
        ->name('admin.applications.pdf');
});

Route::prefix('portal')->name('portal.')->group(function (): void {
    Route::middleware('guest:applicant')->group(function (): void {
        Route::get('sign-in', [PortalAuthController::class, 'signInPage'])->name('sign-in');
        Route::post('sign-in', [PortalAuthController::class, 'requestOtp'])
            ->middleware('throttle:portal.otp')
            ->name('sign-in.submit');
        Route::get('verify', [PortalAuthController::class, 'verifyPage'])->name('verify');
        Route::post('verify', [PortalAuthController::class, 'verify'])->name('verify.submit');
        Route::post('resend', [PortalAuthController::class, 'resend'])
            ->middleware('throttle:portal.otp')
            ->name('resend');
    });

    Route::middleware('auth:applicant')->group(function (): void {
        Route::get('/', PortalDashboardController::class)->name('home');
        Route::post('sign-out', [PortalAuthController::class, 'signOut'])->name('sign-out');

        Route::get('apply', [PortalApplicationController::class, 'show'])->name('apply');
        Route::post('apply/autosave', [PortalApplicationController::class, 'autosave'])->name('apply.autosave');
        Route::post('apply/submit', [PortalApplicationController::class, 'submit'])->name('apply.submit');

        Route::get('application/pdf', ApplicationPdfController::class)->name('application.pdf');
        Route::post('application/payment-proof', PaymentProofController::class)->name('application.payment-proof');

        Route::get('renew', [PortalRenewalController::class, 'show'])->name('renew');
        Route::post('renew/autosave', [PortalRenewalController::class, 'autosave'])->name('renew.autosave');
        Route::post('renew/submit', [PortalRenewalController::class, 'submit'])->name('renew.submit');
    });
});

require __DIR__.'/settings.php';
