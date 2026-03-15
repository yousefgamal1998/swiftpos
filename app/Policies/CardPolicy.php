<?php

namespace App\Policies;

use App\Models\Card;
use App\Models\User;

class CardPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Card $card): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Card $card): bool
    {
        return $user->hasRole('admin');
    }
}
