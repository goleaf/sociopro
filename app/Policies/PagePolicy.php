<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Page;
use App\Models\User;

class PagePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->user_role === UserRole::Admin->value) {
            return true;
        }

        return null;
    }

    public function create(User $user): bool
    {
        return $user->exists;
    }

    public function update(User $user, Page $page): bool
    {
        return (int) $user->id === (int) $page->user_id;
    }

    public function delete(User $user, Page $page): bool
    {
        return (int) $user->id === (int) $page->user_id;
    }
}
