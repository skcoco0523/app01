<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // ★追加

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
        // 本番環境かつ audio/upload 以外のアクセス時のみ HTTPS 通信を強制
        if (config('app.env') === 'production' && !request()->is('api/audio/upload')) {
            URL::forceScheme('https');
        }
    }
}