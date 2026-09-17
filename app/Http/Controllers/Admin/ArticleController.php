<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Silhouette;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArticleRequest;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ArticleController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Article::class);

        return view('admin.articles.index', [
            'articles' => Article::query()
                ->with('category')
                ->withCount(['variants', 'products'])
                ->withSum('variants', 'stock')
                ->orderBy('category_id')->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Article::class);

        return view('admin.articles.form', $this->formData(new Article(['is_active' => true, 'silhouette' => Silhouette::TShirt])));
    }

    public function store(ArticleRequest $request): RedirectResponse
    {
        $article = Article::create($request->attributesForModel());

        return to_route('admin.articles.edit', $article)->with('status', 'Article créé. Ajoutez ses variantes.');
    }

    public function edit(Article $article): View
    {
        Gate::authorize('viewAny', Article::class);

        $article->load(['variants' => fn (HasMany $q) => $q->orderBy('color_name')->orderBy('id'), 'products.marking']);

        return view('admin.articles.form', $this->formData($article));
    }

    public function update(ArticleRequest $request, Article $article): RedirectResponse
    {
        $article->update($request->attributesForModel());

        return to_route('admin.articles.edit', $article)->with('status', 'Article enregistré.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        Gate::authorize('delete', $article);

        $refusal = 'Cet article a des produits : désactivez-le plutôt que de le supprimer.';

        if ($article->products()->exists()) {
            return back()->with('error', $refusal);
        }

        try {
            // The RESTRICT foreign key still has the last word if a product is
            // added meanwhile. The savepoint keeps an outer transaction usable:
            // PostgreSQL aborts the whole transaction on a failed statement.
            DB::transaction(fn () => $article->delete());
        } catch (QueryException) {
            return back()->with('error', $refusal);
        }

        return to_route('admin.articles.index')->with('status', 'Article supprimé.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Article $article): array
    {
        return [
            'article' => $article,
            'categories' => Category::query()->orderBy('position')->get(),
            'silhouettes' => Silhouette::cases(),
        ];
    }
}
