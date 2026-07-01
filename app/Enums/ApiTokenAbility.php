<?php

namespace App\Enums;

enum ApiTokenAbility: string
{
    case MarketplaceCreate = 'marketplace:create';
    case MarketplaceUpdate = 'marketplace:update';
    case MarketplaceDelete = 'marketplace:delete';
}
