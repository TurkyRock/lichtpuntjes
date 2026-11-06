<?php

namespace App\Providers;

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
        \App\Models\User::observe(\App\Observers\UserSecurityObserver::class);
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
            if (app()->bound('request') && request()->hasSession()) {
                request()->session()->put('account_security_version', (int)$event->user->security_version);
                if (!\Illuminate\Support\Facades\Auth::guard($event->guard)->viaRemember()) {
                    request()->session()->put('auth.password_confirmed_at', now()->timestamp);
                } else {
                    request()->session()->forget('auth.password_confirmed_at');
                }
            }
        });
    }
}
