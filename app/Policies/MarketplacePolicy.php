<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Marketplace;
use App\Models\User;

class MarketplacePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($ability === 'messageSeller') {
            return null;
        }

        if ($user->user_role === UserRole::Admin->value) {
            return true;
        }

        return null;
    }

    public function create(User $user): bool
    {
        return $user->exists;
    }

    public function update(User $user, Marketplace $marketplace): bool
    {
        return (int) $user->id === (int) $marketplace->user_id;
    }

    public function delete(User $user, Marketplace $marketplace): bool
    {
        return (int) $user->id === (int) $marketplace->user_id;
    }

    public function messageSeller(User $user, Marketplace $marketplace): bool
    {
        return (int) $user->id !== (int) $marketplace->user_id;
    }
}
