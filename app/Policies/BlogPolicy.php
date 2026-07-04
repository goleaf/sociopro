<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Blog;
use App\Models\User;

class BlogPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->user_role === UserRole::Admin->value) {
            return true;
        }

        return null;
    }

    public function update(User $user, Blog $blog): bool
    {
        return (int) $user->id === (int) $blog->user_id;
    }

    public function delete(User $user, Blog $blog): bool
    {
        return (int) $user->id === (int) $blog->user_id;
    }
}
