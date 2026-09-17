<?php

declare(strict_types=1);

use App\Support\Chf;

it('formats Swiss prices with a non-breaking space before the currency', function (int $cents, string $expected) {
    expect(Chf::format($cents))->toBe($expected);
})->with([
    [3500, "35.–\u{00A0}CHF"],
    [3550, "35.50\u{00A0}CHF"],
    [505, "5.05\u{00A0}CHF"],
    [0, "0.–\u{00A0}CHF"],
    [125000, "1'250.–\u{00A0}CHF"],
    [-500, "-5.–\u{00A0}CHF"],
]);
