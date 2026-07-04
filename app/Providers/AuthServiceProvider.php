<?php

namespace App\Providers;

use App\Models\Marketplace;
use App\Models\Page;
use App\Models\Posts;
use App\Policies\MarketplacePolicy;
use App\Policies\PagePolicy;
use App\Policies\PostPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Marketplace::class => MarketplacePolicy::class,
        Page::class => PagePolicy::class,
        Posts::class => PostPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
