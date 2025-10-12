<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Ensure a request instance exists when running in console so services
        // that depend on the current request (e.g. URL generator) can resolve.
        if ($this->app->runningInConsole() && ! $this->app->bound('request')) {
            $this->app->instance('request', Request::create(
                config('app.url', 'http://127.0.0.1:8000'),
                'GET'
            ));
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Avoid URL operations during console execution where no HTTP request exists.
        if ($this->app->runningInConsole()) {
            return;
        }

        // If your app URL is HTTPS, force scheme to avoid mixed content.
        $appUrl = (string) config('app.url');
        if ($appUrl !== '' && str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }
    }
}
