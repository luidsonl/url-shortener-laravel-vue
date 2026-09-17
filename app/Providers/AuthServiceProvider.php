<?php

namespace App\Providers;

use App\Contracts\AuthServiceInterface;
use App\Services\SanctumService;
use App\Services\JwtService;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(AuthServiceInterface::class, function ($app) {
            $driver = config('auth.guards.api.driver');
            
            if ($driver === 'jwt') {
                return new JwtService();
            }
            
            return new SanctumService();
        });
    }

    public function boot(): void
    {
        //
    }
}