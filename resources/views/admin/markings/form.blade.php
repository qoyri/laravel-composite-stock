@php
    $editing = $marking->exists;
    $canEdit = $editing ? auth()->user()->can('update', $marking) : true;
@endphp

<x-layouts.admin :title="$editing ? $marking->name : 'Nouveau marquage'">
    <a href="{{ route('admin.markings.index') }}" class="mb-6 inline-flex items-center text-xs tracking-wider text-gray-500 uppercase hover:text-black">
        <x-icon name="arrow-left" class="mr-2 h-3 w-3" /> Marquages
    </a>

    <div class="space-y-8">
        <form method="POST" action="{{ $editing ? route('admin.markings.update', $marking) : route('admin.markings.store') }}"
              class="max-w-3xl space-y-6 border border-gray-200 bg-white p-6" x-data="{ unlimited: @js((bool) old('is_unlimited', $marking->is_unlimited)) }">
            @csrf
            @if ($editing) @method('PUT') @endif
            <x-eyebrow as="h2">Fiche marquage</x-eyebrow>

            <fieldset @disabled(! $canEdit) class="space-y-6">
                <x-admin.field name="name" label="Nom / slogan" :value="$marking->name" required />
                <x-admin.field name="slug" label="Slug (auto si vide)" :value="$marking->slug" />
                <x-admin.field name="technique" label="Technique" type="select" required>
                    @foreach ($techniques as $technique)
                        <option value="{{ $technique->value }}" @selected(old('technique', $marking->technique?->value) === $technique->value)>{{ $technique->label() }}</option>
                    @endforeach
                </x-admin.field>
                <div class="grid grid-cols-2 gap-6">
                    <x-admin.field name="ink_color" label="Encre" :value="$marking->ink_color" required />
                    <x-admin.field name="ink_hex" label="Teinte" type="color" :value="$marking->ink_hex" />
                </div>
                <label class="flex items-center gap-2 text-xs tracking-wider uppercase">
                    <input type="hidden" name="is_unlimited" value="0">
                    <input type="checkbox" name="is_unlimited" value="1" x-model="unlimited" class="accent-black"> Impression à la demande (stock illimité)
                </label>
                @unless ($editing)
                    <div x-show="! unlimited">
                        <x-admin.field name="stock" label="Stock initial (unités)" type="number" min="0" value="0" />
                    </div>
                @endunless
                <label class="flex items-center gap-2 text-xs tracking-wider uppercase">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $marking->is_active)) class="accent-black"> Actif
                </label>
            </fieldset>

            @if ($canEdit)
                <button class="btn-primary w-full">Enregistrer</button>
            @else
                <p class="text-xs text-gray-500">Lecture seule : seul un administrateur modifie le catalogue.</p>
            @endif
        </form>

        @if ($editing)
            <div class="space-y-8">
                <section class="border border-gray-200 bg-white p-6">
                    <h2 class="eyebrow mb-4">Stock</h2>
                    @if ($marking->is_unlimited)
                        <p class="text-sm text-gray-600">Impression à la demande : aucun stock n'est décompté.</p>
                    @else
                        <p class="mb-4 text-2xl font-light">{{ $marking->stock }} <span class="text-sm text-gray-500">unités</span></p>
                        @error('delta')<p class="mb-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        @can('adjustStock', $marking)<x-admin.adjust-stock :action="route('admin.stock.markings.adjust', $marking)" />@endcan
                        <p class="mt-3 text-xs text-gray-500">Le stock ne se modifie que par ajustement (+/−), pour que chaque changement soit tracé.</p>
                    @endif
                </section>

                <section>
                    <h2 class="eyebrow mb-4">Derniers mouvements</h2>
                    <x-admin.table :headers="['Date', 'Motif', 'Δ', 'Après', 'Par']">
                        @forelse ($marking->stockMovements as $movement)
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $movement->created_at?->format('d.m.Y H:i') }}</td>
                                <td class="px-6 py-3 text-sm">{{ $movement->reason->label() }} {{ $movement->note ? '· '.$movement->note : '' }}</td>
                                <td @class(['px-6 py-3 text-sm', 'text-green-700' => $movement->delta > 0, 'text-red-600' => $movement->delta < 0])>{{ sprintf('%+d', $movement->delta) }}</td>
                                <td class="px-6 py-3 text-sm">{{ $movement->stock_after }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $movement->user?->name ?? 'Boutique' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-4 text-sm text-gray-500">Aucun mouvement.</td></tr>
                        @endforelse
                    </x-admin.table>
                </section>

                <section>
                    <h2 class="eyebrow mb-4">Proposé sur</h2>
                    <div class="divide-y divide-gray-200 border border-gray-200 bg-white">
                        @forelse ($marking->products as $product)
                            <div class="flex justify-between px-4 py-3 text-sm">
                                <span>{{ $product->article->name }} <span class="text-gray-500">· {{ $product->units_per_item }} u./pièce</span></span>
                                <span>{{ chf($product->price_cents) }}</span>
                            </div>
                        @empty
                            <p class="px-4 py-3 text-sm text-gray-500">Aucun article.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        @endif
    </div>
</x-layouts.admin>
