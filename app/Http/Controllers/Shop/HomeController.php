<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Catalog\ProductCatalog;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(ProductCatalog $catalog): View
    {
        return view('shop.home', [
            'categories' => Category::query()->orderBy('position')->get(),
            'featured' => $catalog->featured(),
            'catalog' => $catalog,
        ]);
    }
}
