<?php

declare(strict_types=1);

return [

    /*
     * All amounts are integer cents of Swiss francs.
     */
    'currency' => 'CHF',

    'shipping' => [
        'fee_cents' => (int) env('SHOP_SHIPPING_FEE_CENTS', 500),
        'free_from_cents' => (int) env('SHOP_FREE_SHIPPING_FROM_CENTS', 5000),
    ],

    'cart' => [
        'max_quantity_per_line' => 20,
    ],

];
