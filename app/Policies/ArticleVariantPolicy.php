<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ArticleVariant;
use App\Models\User;

class ArticleVariantPolicy
{
    /** Receiving textiles is the warehouse's job: staff may do it. */
    public function adjustStock(User $user, ArticleVariant $variant): bool
    {
        return true;
    }
}
