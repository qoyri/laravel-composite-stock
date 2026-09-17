<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Catalog\CatalogSort;
use App\Catalog\ProductCatalog;
use App\Enums\MarkingTechnique;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CatalogRequest;
use App\Models\Category;
use App\Models\Marking;
use Illuminate\Contracts\View\View;

class CatalogController extends Controller
{
    public function __invoke(CatalogRequest $request, ProductCatalog $catalog): View
    {
        $filters = $request->filters();

        return view('shop.catalog', [
            'products' => $catalog->search($filters),
            'filters' => $filters,
            'catalog' => $catalog,
            'categories' => Category::query()->orderBy('position')->get(),
            'currentCategory' => $filters->category !== null ? Category::firstWhere('slug', $filters->category) : null,
            'currentMarking' => $filters->marking !== null ? Marking::firstWhere('slug', $filters->marking) : null,
            'techniques' => MarkingTechnique::cases(),
            'sorts' => CatalogSort::cases(),
        ]);
    }
}
