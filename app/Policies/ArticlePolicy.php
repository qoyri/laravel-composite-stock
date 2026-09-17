<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

/**
 * Staff read the catalogue; only admins change it.
 */
class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Article $article): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->isAdmin();
    }
}
