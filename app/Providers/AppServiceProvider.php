<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
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
        // The app ships Bootstrap 5 + a custom .pagination-ios stylesheet built
        // for Bootstrap's <ul class="pagination"> markup. Laravel's default
        // paginator view renders Tailwind markup instead, which shows up as
        // giant unstyled SVG arrows since Tailwind isn't loaded. This switches
        // every $paginator->links() call app-wide to Bootstrap-compatible markup.
        Paginator::useBootstrap();

        // On shared hosting the SSL is often terminated before PHP, so Laravel
        // doesn't see the request as secure and route()/url() emit http://,
        // which the browser blocks as mixed content on an https:// page.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
