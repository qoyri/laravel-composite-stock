<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\MarkingTechnique;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarkingRequest;
use App\Models\Marking;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class MarkingController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Marking::class);

        return view('admin.markings.index', [
            'markings' => Marking::query()->withCount('products')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Marking::class);

        return view('admin.markings.form', [
            'marking' => new Marking(['is_active' => true, 'technique' => MarkingTechnique::ScreenPrinting, 'ink_hex' => '#111111']),
            'techniques' => MarkingTechnique::cases(),
        ]);
    }

    public function store(MarkingRequest $request): RedirectResponse
    {
        $marking = Marking::create($request->attributesForModel());

        return to_route('admin.markings.edit', $marking)->with('status', 'Marquage créé.');
    }

    public function edit(Marking $marking): View
    {
        Gate::authorize('viewAny', Marking::class);

        return view('admin.markings.form', [
            'marking' => $marking->load(['products.article', 'stockMovements' => fn (MorphMany $q) => $q->latest('id')->limit(10)->with('user')]),
            'techniques' => MarkingTechnique::cases(),
        ]);
    }

    public function update(MarkingRequest $request, Marking $marking): RedirectResponse
    {
        $marking->update($request->attributesForModel());

        return to_route('admin.markings.edit', $marking)->with('status', 'Marquage enregistré.');
    }
}
