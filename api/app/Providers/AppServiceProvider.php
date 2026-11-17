<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind a null organization context by default.
        // SetOrganizationContext middleware overwrites these per-request.
        $this->app->instance('current_organization_id', null);
        $this->app->instance('current_organization', null);
    }

    public function boot(): void
    {
        // Force HTTPS in production
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
