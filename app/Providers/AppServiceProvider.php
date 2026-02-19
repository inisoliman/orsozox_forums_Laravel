<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\Forum;
use Illuminate\Support\Facades\Cache;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
            \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Share forums globally for navbar
        View::composer('layouts.app', function ($view) {
            $forums = Cache::remember('nav_forums', 3600, function () {
                return Forum::active()
                    ->root()
                    ->ordered()
                    ->with([
                        'children' => function ($q) {
                            $q->active()->ordered();
                        }
                    ])
                    ->get();
            });
            $view->with('navForums', $forums);
        });
    }
}
