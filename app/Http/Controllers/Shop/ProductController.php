<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Cart\Cart;
use App\Catalog\ProductCatalog;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Stock\AvailabilityCalculator;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    /**
     * {sellableProduct} is bound in AppServiceProvider: inactive products 404.
     */
    public function __invoke(Product $sellableProduct, ProductCatalog $catalog, AvailabilityCalculator $availability, Cart $cart): View
    {
        $product = $catalog->loadForPage($sellableProduct);

        // Everything Alpine needs to switch colour and size without a request.
        $variants = $product->article->variants->map(fn ($variant) => [
            'id' => $variant->id,
            'color' => $variant->color_name,
            'hex' => $variant->color_hex,
            'size' => $variant->size->value,
            'sizeLabel' => $variant->size->label(),
            'available' => max(0, $availability->quantity($product, $variant) - $cart->quantityOf($product->id, $variant->id)),
        ])->values();

        return view('shop.product', [
            'product' => $product,
            'variants' => $variants,
            'sameArticle' => $catalog->otherMarkingsOf($product->article, $product),
            'sameMarking' => $catalog->otherArticlesWith($product->marking, $product),
            'catalog' => $catalog,
        ]);
    }
}
