<?php

namespace App\Providers;

use App\Models\User;
use App\Nova\Application;
use App\Nova\Dashboards\Main;
use App\Nova\EmailTemplate;
use Illuminate\Support\Facades\Gate;
use Laravel\Fortify\Features;
use Laravel\Nova\Dashboard;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;
use Laravel\Nova\Tool;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        //
    }

    /**
     * Register the configurations for Laravel Fortify.
     */
    protected function fortify(): void
    {
        Nova::fortify()
            ->features([
                Features::updatePasswords(),
                // Features::emailVerification(),
                // Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true]),
                // Features::passkeys(),
            ])
            ->register();
    }

    /**
     * Register the Nova routes.
     */
    protected function routes(): void
    {
        Nova::routes()
            ->withAuthenticationRoutes()
            ->withPasswordResetRoutes()
            ->withoutEmailVerificationRoutes()
            ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     */
    /**
     * Nova access is restricted to an explicit allowlist of admin emails
     * from the NOVA_ADMIN_EMAILS env (comma-separated). In local dev we
     * also admit the seeded test user so you can log in without extra
     * config.
     */
    protected function gate(): void
    {
        Gate::define('viewNova', function (User $user): bool {
            $raw = (string) env('NOVA_ADMIN_EMAILS', '');
            $allowed = collect(explode(',', $raw))
                ->map(fn ($email) => mb_strtolower(trim($email)))
                ->filter()
                ->all();

            if (app()->environment('local') && empty($allowed)) {
                $allowed[] = 'test@example.com';
            }

            return in_array(mb_strtolower($user->email), $allowed, strict: true);
        });
    }

    /**
     * Get the dashboards that should be listed in the Nova sidebar.
     *
     * @return array<int, Dashboard>
     */
    protected function resources(): void
    {
        Nova::resources([
            \App\Nova\User::class,
            Application::class,
            EmailTemplate::class,
        ]);
    }

    protected function dashboards(): array
    {
        return [
            new Main,
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array<int, Tool>
     */
    public function tools(): array
    {
        return [];
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        parent::register();

        //
    }
}
