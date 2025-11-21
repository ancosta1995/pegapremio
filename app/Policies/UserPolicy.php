<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine if the user can access the Filament admin panel.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_admin ?? false;
    }
}

