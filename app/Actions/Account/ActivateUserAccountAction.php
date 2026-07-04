<?php

namespace App\Actions\Account;

use App\Models\User;

class ActivateUserAccountAction
{
    public function handle(User $user): User
    {
        if ($user->hasActiveAccount()) {
            return $user;
        }

        $user->activateAccount();

        return $user;
    }
}
