<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CommonServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        view()->share('copy_right', config('app.name', 'Sociopro'));
    }
}
