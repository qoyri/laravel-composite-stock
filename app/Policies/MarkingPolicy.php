<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Marking;
use App\Models\User;

class MarkingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Marking $marking): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Marking $marking): bool
    {
        return $user->isAdmin();
    }

    /** Receiving consumables is the warehouse's job: staff may do it. */
    public function adjustStock(User $user, Marking $marking): bool
    {
        return true;
    }
}
