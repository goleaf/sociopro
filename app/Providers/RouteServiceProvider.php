<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/';

    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/custom_routes.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/user.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/payment.php'));

            if ($this->shouldLoadFundraiserRoutes()) {
                Route::middleware('web')
                    ->namespace($this->namespace)
                    ->group(base_path('routes/fundraiser.php'));
            }
        });
    }

    private function shouldLoadFundraiserRoutes(): bool
    {
        try {
            if (DB::connection()->getDatabaseName() === 'db_name') {
                return false;
            }

            return Schema::hasTable('addons') && addon_status('fundraiser') == 1;
        } catch (Throwable $exception) {
            return false;
        }
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
