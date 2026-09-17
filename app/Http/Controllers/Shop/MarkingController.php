<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Marking;
use App\Models\Product;
use Illuminate\Contracts\View\View;

/**
 * "Personnaliser": every marking and the garments it can be applied to.
 */
class MarkingController extends Controller
{
    public function __invoke(): View
    {
        $markings = Marking::query()->active()->orderBy('name')->get();

        return view('shop.markings', [
            'markings' => $markings,
            'productsByMarking' => Product::query()
                ->sellable()
                ->with('article')
                ->whereIn('marking_id', $markings->modelKeys())
                ->orderBy('price_cents')
                ->get()
                ->groupBy('marking_id'),
        ]);
    }
}
