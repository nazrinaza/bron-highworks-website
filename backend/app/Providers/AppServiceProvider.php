<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::define('admin', fn (User $user): bool => (bool) $user->is_admin);
        RateLimiter::for('admin-login', fn (Request $request) => [Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()), Limit::perMinute(30)->by($request->ip())]);
    }
}
