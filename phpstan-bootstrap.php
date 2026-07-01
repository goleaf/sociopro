<?php

declare(strict_types=1);
use Anand\LaravelPaytmWallet\Facades\PaytmWallet;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Intervention\Image\Facades\Image;
use Jorenvh\Share\ShareFacade;

$aliases = [
    'Artisan' => Artisan::class,
    'Auth' => Auth::class,
    'auth' => Auth::class,
    'Cache' => Cache::class,
    'DB' => DB::class,
    'Image' => Image::class,
    'Mail' => Mail::class,
    'PaytmWallet' => PaytmWallet::class,
    'Session' => Session::class,
    'Share' => ShareFacade::class,
];

foreach ($aliases as $alias => $class) {
    if (class_exists($class) && ! class_exists($alias, false)) {
        class_alias($class, $alias);
    }
}
