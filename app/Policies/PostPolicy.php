<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Posts;
use App\Models\User;

class PostPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->user_role === UserRole::Admin->value) {
            return true;
        }

        return null;
    }

    public function update(User $user, Posts $post): bool
    {
        return (int) $user->id === (int) $post->user_id;
    }

    public function delete(User $user, Posts $post): bool
    {
        return (int) $user->id === (int) $post->user_id;
    }
}
