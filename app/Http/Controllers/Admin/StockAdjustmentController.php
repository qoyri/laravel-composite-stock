<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Stock\AdjustStock;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockAdjustmentRequest;
use App\Models\ArticleVariant;
use App\Models\Marking;
use App\Models\StockMovement;
use App\Models\User;
use App\Stock\InvalidStockAdjustment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class StockAdjustmentController extends Controller
{
    /**
     * The movement log, across both component types.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', StockMovement::class);

        return view('admin.stock.index', [
            'movements' => StockMovement::query()
                ->with(['user', 'order', 'stockable'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(30)
                // Nested eager load on the polymorphic relation: variants also need their article.
                ->loadMorph('stockable', [ArticleVariant::class => ['article'], Marking::class => []]),
        ]);
    }

    public function adjustVariant(StockAdjustmentRequest $request, ArticleVariant $variant, AdjustStock $adjustStock): RedirectResponse
    {
        return $this->adjust($request, $variant, $adjustStock);
    }

    public function adjustMarking(StockAdjustmentRequest $request, Marking $marking, AdjustStock $adjustStock): RedirectResponse
    {
        return $this->adjust($request, $marking, $adjustStock);
    }

    private function adjust(StockAdjustmentRequest $request, ArticleVariant|Marking $stockable, AdjustStock $adjustStock): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $stockable = $adjustStock->handle(
                $stockable,
                $request->integer('delta'),
                $user,
                $request->filled('note') ? $request->string('note')->toString() : null,
            );
        } catch (InvalidStockAdjustment $e) {
            return back()->withErrors(['delta' => $e->getMessage()]);
        }

        return back()->with('status', "Stock mis à jour : {$stockable->stock}.");
    }
}
