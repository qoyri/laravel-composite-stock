<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Article;
use App\Models\Marking;
use App\Models\Product;
use App\Stock\AvailabilityCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function index(AvailabilityCalculator $availability): View
    {
        Gate::authorize('viewAny', Product::class);

        $products = Product::query()
            ->with(['article.variants', 'marking'])
            ->withSum('orderLines', 'quantity')
            ->orderBy('marking_id')->orderBy('article_id')
            ->get();

        $sellable = $products->mapWithKeys(fn (Product $p) => [
            $p->id => $availability->total($p, $p->article->variants),
        ]);

        return view('admin.products.index', [
            // What is about to run out comes first. Computed, so sorted in PHP.
            'products' => $products->sortBy(fn (Product $p) => $sellable[$p->id])->values(),
            'availability' => $sellable,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Product::class);

        return view('admin.products.form', [
            'product' => new Product(['units_per_item' => 1, 'is_active' => true]),
            'articles' => Article::query()->orderBy('name')->get(),
            'markings' => Marking::query()->orderBy('name')->get(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        Product::create($request->attributesForModel());

        return to_route('admin.products.index')->with('status', 'Produit créé.');
    }

    public function edit(Product $product): View
    {
        Gate::authorize('update', $product);

        return view('admin.products.form', [
            'product' => $product->load(['article', 'marking']),
            'articles' => collect(),
            'markings' => collect(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->attributesForModel());

        return to_route('admin.products.index')->with('status', 'Produit enregistré.');
    }
}
