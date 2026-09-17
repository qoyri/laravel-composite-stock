<?php

declare(strict_types=1);

use App\Support\Chf;

it('formats Swiss prices', function (int $cents, string $expected) {
    expect(Chf::format($cents))->toBe($expected);
})->with([
    [3500, '35.– CHF'],
    [3550, '35.50 CHF'],
    [505, '5.05 CHF'],
    [0, '0.– CHF'],
    [125000, "1'250.– CHF"],
    [-500, '-5.– CHF'],
]);
