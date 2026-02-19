<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Auth\VBulletinUserProvider;

class VBulletinAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Auth::provider('vbulletin', function ($app, array $config) {
            return new VBulletinUserProvider();
        });
    }
}
