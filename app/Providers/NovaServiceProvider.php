<?php

namespace App\Providers;

use App\Models\User;
use Geosource\Documentation\Documentation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        Nova::withBreadcrumbs();

        Nova::footer(function () {
            return \Illuminate\Support\Facades\Blade::render('
                <p class="text-center">GEOSource Admin &copy; {{ date("Y") }}</p>
            ');
        });
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

        // Custom documentation route
        Route::middleware(['web', \Laravel\Nova\Http\Middleware\Authenticate::class, \Laravel\Nova\Http\Middleware\Authorize::class])
            ->prefix('nova')
            ->group(function () {
                Route::get('/documentation', [\App\Http\Controllers\Nova\DocumentationController::class, 'index'])
                    ->name('nova.documentation');
            });
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewNova', function (User $user) {
            return $user->is_admin;
        });
    }

    /**
     * Get the dashboards that should be listed in the Nova sidebar.
     *
     * @return array<int, \Laravel\Nova\Dashboard>
     */
    protected function dashboards(): array
    {
        return [
            new \App\Nova\Dashboards\Main,
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array<int, \Laravel\Nova\Tool>
     */
    public function tools(): array
    {
        return [
            new Documentation,
        ];
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
