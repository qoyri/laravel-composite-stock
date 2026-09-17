@props(['product', 'available'])

@php
    // Primary and hover previews: two different colours, in-stock first.
    $colors = $product->article->variants
        ->sortByDesc(fn ($v) => $v->stock > 0)
        ->unique('color_name')
        ->values();
    $primary = $colors->first();
    $hover = $colors->get(1);
@endphp

<a href="{{ route('products.show', $product->slug) }}" class="group block bg-white">
    <div class="relative aspect-[3/4] overflow-hidden bg-gray-100">
        @if ($primary)
            <div @class(['absolute inset-0 p-6 transition-all duration-500 ease-in-out', 'group-hover:scale-105 group-hover:opacity-0' => $hover, 'group-hover:scale-105' => ! $hover])>
                <x-garment-preview :silhouette="$product->article->silhouette" :color="$primary->color_hex" :ink="$product->marking->ink_hex"
                                   :text="$product->marking->name" :technique="$product->marking->technique" />
            </div>
        @endif
        @if ($hover)
            <div class="absolute inset-0 p-6 opacity-0 transition-all duration-500 ease-in-out group-hover:scale-105 group-hover:opacity-100">
                <x-garment-preview :silhouette="$product->article->silhouette" :color="$hover->color_hex" :ink="$product->marking->ink_hex"
                                   :text="$product->marking->name" :technique="$product->marking->technique" />
            </div>
        @endif

        <div class="absolute top-4 left-4 z-10 bg-white px-2 py-1 text-xs tracking-wider uppercase">{{ $product->marking->technique->label() }}</div>
        @if ($available === 0)
            <div class="absolute top-4 right-4 z-10 bg-black px-2 py-1 text-[0.625rem] font-medium tracking-wider text-white uppercase">Épuisé</div>
        @endif
    </div>

    <div class="p-4">
        <h3 class="mb-1 text-sm font-medium tracking-wide">{{ $product->marking->name }}</h3>
        <p class="text-xs tracking-wider text-gray-500 uppercase">{{ $product->article->name }}</p>
        <p class="mt-2 text-sm text-gray-600">{{ chf($product->price_cents) }}</p>
    </div>
</a>
