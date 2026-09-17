@php
    $title = $currentMarking?->name ?? $currentCategory?->name ?? ($filters->search ? 'Recherche « '.$filters->search.' »' : 'Tous les produits');
    $query = fn (array $changes) => route('catalog', array_filter([...request()->except('page'), ...$changes], fn ($v) => $v !== null && $v !== ''));
@endphp

<x-layouts.shop :title="$title">
    <x-breadcrumb :items="$currentCategory ? ['Produits' => route('catalog'), $currentCategory->name => null] : ['Produits' => null]" />

    <div class="flex flex-col md:flex-row">
        {{-- Filters --}}
        <aside class="border-b border-gray-200 md:sticky md:top-[64px] md:h-[calc(100vh-64px)] md:w-64 md:shrink-0 md:overflow-y-auto md:border-r md:border-b-0">
            <form method="GET" action="{{ route('catalog') }}" class="p-6" x-data @change="$el.requestSubmit()">
                @if ($filters->category)<input type="hidden" name="categorie" value="{{ $filters->category }}">@endif
                @if ($filters->marking)<input type="hidden" name="marquage" value="{{ $filters->marking }}">@endif

                <div class="mb-6">
                    <label for="q" class="label">Recherche</label>
                    <input id="q" type="search" name="q" value="{{ $filters->search }}" class="input-box" placeholder="Slogan, vêtement…">
                </div>

                <div class="mb-6">
                    <h3 class="label">Catégorie</h3>
                    <div class="space-y-1">
                        <a href="{{ $query(['categorie' => null]) }}" @class(['block w-full px-3 py-2 text-left text-sm', 'bg-black text-white' => ! $filters->category, 'hover:bg-gray-100' => $filters->category])>Toutes</a>
                        @foreach ($categories as $category)
                            <a href="{{ $query(['categorie' => $category->slug]) }}" @class(['block w-full px-3 py-2 text-left text-sm', 'bg-black text-white' => $filters->category === $category->slug, 'hover:bg-gray-100' => $filters->category !== $category->slug])>{{ $category->name }}</a>
                        @endforeach
                    </div>
                </div>

                <div class="mb-6">
                    <h3 class="label">Technique</h3>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 border px-3 py-2 text-sm {{ $filters->technique === null ? 'border-black' : 'border-gray-200 hover:border-gray-400' }}">
                            <input type="radio" name="technique" value="" @checked($filters->technique === null) class="accent-black">
                            Toutes
                        </label>
                        @foreach ($techniques as $technique)
                            <label class="flex items-center gap-2 border px-3 py-2 text-sm {{ $filters->technique === $technique ? 'border-black' : 'border-gray-200 hover:border-gray-400' }}">
                                <input type="radio" name="technique" value="{{ $technique->value }}" @checked($filters->technique === $technique) class="accent-black">
                                {{ $technique->label() }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="mb-6">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="disponible" value="1" @checked($filters->availableOnly) class="accent-black">
                        Disponible uniquement
                    </label>
                </div>

                <div class="mb-6">
                    <label for="tri" class="label">Trier par</label>
                    <select id="tri" name="tri" class="input-box">
                        @foreach ($sorts as $sort)
                            <option value="{{ $sort->value }}" @selected($filters->sort === $sort)>{{ $sort->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <noscript><button class="btn-secondary w-full">Filtrer</button></noscript>
                @if ($filters->isFiltered())
                    <a href="{{ route('catalog') }}" class="block w-full border border-gray-300 px-4 py-2 text-center text-sm tracking-wider uppercase hover:border-black">Réinitialiser</a>
                @endif
            </form>
        </aside>

        {{-- Grid --}}
        <section class="flex-1">
            <div class="border-b border-gray-200 px-6 py-8">
                <h1 class="mb-2 text-3xl font-light tracking-wider uppercase">{{ $title }}</h1>
                @if ($currentMarking)
                    <p class="text-sm text-gray-600">{{ $currentMarking->technique->label() }} · encre {{ mb_strtolower($currentMarking->ink_color) }}</p>
                @endif
                <p class="mt-4 text-sm text-gray-600">{{ $products->total() }} {{ $products->total() > 1 ? 'produits' : 'produit' }}</p>
            </div>

            <div class="p-6">
                @if ($products->isEmpty())
                    <div class="py-20 text-center">
                        <h3 class="mb-2 text-lg font-light tracking-wider uppercase">Aucun produit</h3>
                        <p class="mb-6 text-sm text-gray-600">Essayez d'élargir vos filtres.</p>
                        <a href="{{ route('catalog') }}" class="border border-black px-6 py-2 text-sm tracking-wider uppercase hover:bg-black hover:text-white">Voir tout</a>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-px bg-gray-200 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($products as $product)
                            <x-product-card :product="$product" :available="$catalog->availabilityOf($product)" />
                        @endforeach
                    </div>
                    <div class="mt-10">{{ $products->links('shop.partials.pagination') }}</div>
                @endif
            </div>
        </section>
    </div>
</x-layouts.shop>
