<?php

declare(strict_types=1);

use App\Support\Chf;

if (! function_exists('chf')) {
    function chf(int $cents): string
    {
        return Chf::format($cents);
    }
}
