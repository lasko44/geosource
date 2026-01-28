<?php

namespace Geosource\UserActions;

use Geosource\UserActions\Http\Middleware\Authorize;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class ToolServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(function () {
            $this->routes();
        });

        Nova::serving(function (ServingNova $event) {
            Nova::script('user-actions', __DIR__.'/../dist/js/tool.js');
        });
    }

    protected function routes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['nova', 'nova.auth', Authorize::class])
            ->prefix('nova-vendor/user-actions')
            ->group(__DIR__.'/../routes/api.php');
    }

    public function register(): void
    {
        //
    }
}
