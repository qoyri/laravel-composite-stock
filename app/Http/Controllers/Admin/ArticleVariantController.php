<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVariantRequest;
use App\Models\Article;
use App\Models\ArticleVariant;
use App\Models\OrderLine;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ArticleVariantController extends Controller
{
    public function store(StoreVariantRequest $request, Article $article): RedirectResponse
    {
        $article->variants()->create($request->validated());

        return back()->with('status', 'Variante ajoutée.');
    }

    public function destroy(Article $article, ArticleVariant $variant): RedirectResponse
    {
        Gate::authorize('update', $article);

        $refusal = 'Cette variante a déjà été vendue : elle ne peut plus être supprimée.';

        if (OrderLine::where('article_variant_id', $variant->id)->exists()) {
            return back()->with('error', $refusal);
        }

        try {
            DB::transaction(fn () => $variant->delete());   // see ArticleController::destroy()
        } catch (QueryException) {
            return back()->with('error', $refusal);
        }

        return back()->with('status', 'Variante supprimée.');
    }
}
