<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\Forum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        // حل مشكلة استضافة المجلد الفرعي
        \Livewire\Livewire::setUpdateRoute(function ($handle) {
            \Illuminate\Support\Facades\Route::post('/livewire/update', $handle)->name('livewire.update.internal');
            return \Illuminate\Support\Facades\Route::post('/forums/livewire/update', $handle)->name('livewire.update');
        });

        \Livewire\Livewire::setScriptRoute(function ($handle) {
            \Illuminate\Support\Facades\Route::get('/livewire/livewire.js', $handle)->name('livewire.script.internal');
            return \Illuminate\Support\Facades\Route::get('/forums/livewire/livewire.js', $handle)->name('livewire.script');
        });

        // Share settings globally
        View::share('themeSettings', new \App\Services\ThemeSettings());

        if ((bool) env('PERFORMANCE_QUERY_LOG', false)) {
            DB::listen(function ($query): void {
                $threshold = (int) env('PERFORMANCE_SLOW_QUERY_MS', 250);
                if ($query->time >= $threshold) {
                    Log::warning('slow_query', [
                        'time_ms' => $query->time,
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                    ]);
                }
            });
        }

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

            // Share active news tickers for the ticker bar (moved from view query)
            try {
                $tickers = Cache::remember('active_news_tickers', 600, function () {
                    return \App\Models\NewsTicker::where('is_active', true)
                        ->orderBy('sort_order', 'asc')
                        ->get();
                });
            } catch (\Exception $e) {
                $tickers = collect();
            }
            $view->with('tickers', $tickers);
        });
    }
}
