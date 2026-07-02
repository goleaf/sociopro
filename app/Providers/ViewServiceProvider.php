<?php

namespace App\Providers;

use App\Models\Chat;
use App\Models\Notification as UserNotification;
use App\Models\Setting;
use App\ViewModels\BladeViewData;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('*', function ($view): void {
            $view->with('viewData', app(BladeViewData::class));

            if (! array_key_exists('user_info', $view->getData())) {
                $view->with('user_info', Auth::user());
            }
        });

        View::composer('auth.layout.header', function ($view): void {
            $settings = $this->settings([
                'system_name',
                'system_fav_icon',
                'system_light_logo',
            ]);

            $view->with([
                'systemName' => $settings->get('system_name', config('app.name')),
                'systemFavicon' => $settings->get('system_fav_icon', ''),
                'systemLightLogo' => $settings->get('system_light_logo', ''),
                'homeUrl' => Auth::check() && Route::has('timeline') ? route('timeline') : route('login'),
            ]);
        });

        View::composer(['errors.404', 'errors::404'], function ($view): void {
            $settings = $this->settings([
                'system_name',
                'system_fav_icon',
            ]);

            $view->with([
                'systemName' => $settings->get('system_name', config('app.name')),
                'systemFavicon' => $settings->get('system_fav_icon', ''),
            ]);
        });

        View::composer('backend.footer', function ($view): void {
            $settings = $this->settings([
                'system_footer',
                'system_footer_link',
            ]);

            $view->with([
                'siteFooter' => $settings->get('system_footer', config('app.name')),
                'siteFooterUrl' => $settings->get('system_footer_link', url('/')),
            ]);
        });

        View::composer('backend.common_scripts', function ($view): void {
            $settings = $this->settings([
                'google_analytics_id',
                'meta_pixel_id',
            ]);

            $view->with([
                'googleAnalyticsId' => $settings->get('google_analytics_id'),
                'metaPixelId' => $settings->get('meta_pixel_id'),
            ]);
        });

        View::composer('frontend.toaster', function ($view): void {
            $view->with([
                'successMessage' => Session::pull('success_message'),
                'infoMessage' => Session::pull('info_message'),
                'errorMessage' => Session::pull('error_message'),
            ]);
        });

        View::composer(['frontend.index', 'frontend.disable_view'], function ($view): void {
            $settings = $this->settings([
                'system_name',
                'system_fav_icon',
                'theme_color',
            ]);

            $themeColor = Session::get('theme_color', 'default');

            $view->with([
                'systemName' => $settings->get('system_name', config('app.name')),
                'systemFavicon' => $settings->get('system_fav_icon', ''),
                'themeColor' => $settings->get('theme_color', ''),
                'theme_color' => $themeColor,
                'image' => $themeColor === 'dark'
                    ? asset('assets/frontend/images/white_sun.svg')
                    : asset('assets/frontend/images/white_moon.svg'),
                'user_info' => Auth::user(),
            ]);
        });

        View::composer(['frontend.header', 'frontend.disable_view'], function ($view): void {
            $settings = $this->settings(['system_light_logo']);
            $themeColor = Session::get('theme_color', 'default');
            $user = Auth::user();
            $lastMessage = null;
            $messageTo = null;
            $unreadMessageCount = 0;
            $unreadNotificationCount = 0;
            $newNotifications = collect();
            $olderNotifications = collect();

            if ($user) {
                $lastMessage = Chat::query()
                    ->where('sender_id', $user->id)
                    ->orWhere('reciver_id', $user->id)
                    ->orderByDesc('id')
                    ->first();

                $messageTo = $lastMessage
                    ? ($lastMessage->sender_id == $user->id ? $lastMessage->reciver_id : $lastMessage->sender_id)
                    : null;

                $unreadMessageCount = Chat::query()
                    ->where('reciver_id', $user->id)
                    ->where('read_status', '0')
                    ->count();

                $unreadNotificationCount = UserNotification::query()
                    ->where('reciver_user_id', $user->id)
                    ->where('status', '0')
                    ->count();

                $newNotifications = UserNotification::query()
                    ->where('reciver_user_id', $user->id)
                    ->where('status', '0')
                    ->orderByDesc('id')
                    ->get();

                $olderNotifications = UserNotification::query()
                    ->where('reciver_user_id', $user->id)
                    ->where('created_at', '<', Carbon::today())
                    ->orderByDesc('id')
                    ->get();
            }

            $view->with([
                'systemLightLogo' => $settings->get('system_light_logo', ''),
                'theme_color' => $themeColor,
                'image' => $themeColor === 'dark'
                    ? asset('assets/frontend/images/white_sun.svg')
                    : asset('assets/frontend/images/white_moon.svg'),
                'msg_to' => $messageTo,
                'unread_msg' => $unreadMessageCount,
                'unread_notification' => $unreadNotificationCount,
                'new_notification' => $newNotifications,
                'older_notification' => $olderNotifications,
            ]);
        });

        View::composer('frontend.live_streaming.index', function ($view): void {
            $zoomConfiguration = get_settings('zoom_configuration', true);

            $view->with('zoom_configuration', [
                'api_key' => is_array($zoomConfiguration) ? (string) ($zoomConfiguration['api_key'] ?? '') : '',
            ]);
        });

        View::composer('frontend.main_content.jitsi_streaming', function ($view): void {
            $storedConfiguration = get_settings('zitsi_configuration', true);
            $jitsiConfiguration = array_replace([
                'account_email' => '',
                'jitsi_app_id' => '',
                'jitsi_jwt' => '',
            ], is_array($storedConfiguration) ? $storedConfiguration : []);

            foreach (['account_email', 'jitsi_app_id', 'jitsi_jwt'] as $key) {
                $jitsiConfiguration[$key] = is_scalar($jitsiConfiguration[$key]) ? (string) $jitsiConfiguration[$key] : '';
            }

            $view->with([
                'jitsis' => $jitsiConfiguration,
                'jitsiAppId' => $jitsiConfiguration['jitsi_app_id'],
                'leaveUrl' => route('timeline'),
            ]);
        });
    }

    private function settings(array $types)
    {
        try {
            return Setting::query()
                ->select(['type', 'description'])
                ->whereIn('type', $types)
                ->pluck('description', 'type');
        } catch (Throwable) {
            return collect();
        }
    }
}
