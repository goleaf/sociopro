<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Comments;
use App\Models\User;

class CommentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->user_role === UserRole::Admin->value) {
            return true;
        }

        return null;
    }

    public function delete(User $user, Comments $comment): bool
    {
        return (int) $user->id === (int) $comment->user_id;
    }
}
