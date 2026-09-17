<x-layouts.shop title="Personnaliser">
    <x-breadcrumb :items="['Personnaliser' => null]" />

    <div class="mx-auto max-w-screen-2xl px-6 py-12">
        <div class="mb-12 max-w-2xl">
            <h1 class="mb-4 text-3xl font-light tracking-wider uppercase">Choisissez votre marquage</h1>
            <p class="text-sm leading-relaxed text-gray-600">
                Chaque marquage se pose sur plusieurs vêtements. Broderie et sérigraphie utilisent des fournitures en quantité limitée ;
                le flocage est imprimé à la demande.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-px bg-gray-200 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($markings as $marking)
                @php
                    $products = $productsByMarking->get($marking->id, collect());
                    $first = $products->first();
                @endphp
                <div class="flex flex-col bg-white">
                    <a href="{{ route('catalog', ['marquage' => $marking->slug]) }}" class="group relative block aspect-[4/3] overflow-hidden bg-gray-100">
                        @if ($first)
                            <div class="absolute inset-0 p-6 transition-transform duration-500 group-hover:scale-105">
                                <x-garment-preview :silhouette="$first->article->silhouette" :color="$marking->ink_hex === '#FFFFFF' ? '#1F2A44' : '#F7F7F2'"
                                                   :ink="$marking->ink_hex" :text="$marking->name" :technique="$marking->technique" />
                            </div>
                        @endif
                        <div class="absolute top-4 left-4 bg-white px-2 py-1 text-xs tracking-wider uppercase">{{ $marking->technique->label() }}</div>
                    </a>
                    <div class="flex flex-1 flex-col p-6">
                        <h2 class="font-humor text-xl">{{ $marking->name }}</h2>
                        <p class="mt-1 text-xs tracking-wider text-gray-500 uppercase">
                            Encre {{ mb_strtolower($marking->ink_color) }} ·
                            @if ($marking->is_unlimited) impression à la demande
                            @elseif ($marking->stock === 0) <span class="text-red-600">fournitures épuisées</span>
                            @else fournitures en stock
                            @endif
                        </p>
                        <ul class="mt-4 flex-1 space-y-1 text-sm text-gray-600">
                            @foreach ($products as $product)
                                <li class="flex justify-between">
                                    <a href="{{ route('products.show', $product->slug) }}" class="hover:text-black hover:underline">{{ $product->article->name }}</a>
                                    <span>{{ chf($product->price_cents) }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ route('catalog', ['marquage' => $marking->slug]) }}" class="mt-6 text-xs tracking-wider uppercase hover:underline">
                            {{ $products->count() }} {{ $products->count() > 1 ? 'vêtements' : 'vêtement' }} →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.shop>
