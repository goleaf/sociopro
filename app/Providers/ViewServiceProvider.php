<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('auth.layout.header', function ($view): void {
            $settings = collect();

            try {
                $settings = Setting::query()
                    ->select(['type', 'description'])
                    ->whereIn('type', ['system_name', 'system_fav_icon', 'system_light_logo'])
                    ->pluck('description', 'type');
            } catch (Throwable $exception) {
                $settings = collect();
            }

            $view->with([
                'systemName' => $settings->get('system_name', config('app.name')),
                'systemFavicon' => $settings->get('system_fav_icon', ''),
                'systemLightLogo' => $settings->get('system_light_logo', ''),
            ]);
        });
    }
}
