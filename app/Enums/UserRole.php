<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    /** Full back-office access, including catalogue changes and cancellations. */
    case Admin = 'admin';

    /** Warehouse staff: reads everything, adjusts stock, changes nothing else. */
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Staff => 'Préparateur',
        };
    }
}
