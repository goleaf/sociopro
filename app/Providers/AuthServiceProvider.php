<?php

namespace App\Providers;

use App\Models\Marketplace;
use App\Policies\MarketplacePolicy;
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
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
