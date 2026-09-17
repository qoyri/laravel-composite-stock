<?php

declare(strict_types=1);

namespace App\Enums;

enum MarkingTechnique: string
{
    case Embroidery = 'embroidery';
    case Flocking = 'flocking';
    case ScreenPrinting = 'screen_printing';

    public function label(): string
    {
        return match ($this) {
            self::Embroidery => 'Broderie',
            self::Flocking => 'Flocage',
            self::ScreenPrinting => 'Sérigraphie',
        };
    }
}
