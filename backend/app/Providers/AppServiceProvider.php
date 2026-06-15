<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            $email = (string) $request->input('email', '');

            return [
                Limit::perMinute(10)->by($request->ip()),
                Limit::perMinute(5)->by($email !== '' ? 'email:' . mb_strtolower($email) : $request->ip()),
            ];
        });

        RateLimiter::for('contact', fn (Request $request) => [
            Limit::perMinute(3)->by($request->ip()),
            Limit::perDay(20)->by($request->ip()),
        ]);

        RateLimiter::for('questions', fn (Request $request) => [
            Limit::perHour(10)->by($request->user()?->id ?: $request->ip()),
        ]);

        RateLimiter::for('votes', fn (Request $request) => [
            Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()),
        ]);
    }
}
