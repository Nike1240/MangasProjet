<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Password;

use Illuminate\Auth\Notifications\ResetPassword;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot()
    {
        $this->registerPolicies();

        // Remplacez la méthode précédente par celle-ci
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return config('app.frontend_url').'/reset-password?token='.$token.'&email='.$user->email;
        });
    }
}
