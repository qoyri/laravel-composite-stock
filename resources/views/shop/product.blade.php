@php
    $article = $product->article;
    $marking = $product->marking;
    $colors = $article->variants->unique('color_name')->map(fn ($v) => ['name' => $v->color_name, 'hex' => $v->color_hex])->values();
    $initialColor = $variants->firstWhere(fn ($v) => $v['available'] > 0)['color'] ?? $colors->first()['name'] ?? null;
@endphp

<x-layouts.shop :title="$product->displayName()" :description="$marking->name.' en '.$marking->technique->label().' sur '.$article->name.'.'">
    <x-breadcrumb :items="[
        $article->category->name => route('catalog', ['categorie' => $article->category->slug]),
        $product->displayName() => null,
    ]" />

    <div class="py-8 lg:py-12"
         x-data="{
            variants: @js($variants),
            color: @js($initialColor),
            size: null,
            qty: 1,
            get sizes() { return this.variants.filter(v => v.color === this.color) },
            get selected() { return this.variants.find(v => v.color === this.color && v.size === this.size) ?? null },
            get available() { return this.selected ? this.selected.available : Math.max(0, ...this.sizes.map(v => v.available)) },
            colorAvailable(c) { return this.variants.some(v => v.color === c && v.available > 0) },
            pickColor(c) { this.color = c; if (! this.selected || this.selected.available === 0) { this.size = null } this.qty = 1 },
            pickSize(s) { this.size = s; this.qty = Math.min(this.qty, Math.max(1, this.available)) },
         }">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('catalog') }}" class="inline-flex items-center text-xs tracking-wider text-gray-600 uppercase hover:text-black">
                    <x-icon name="chevron-left" class="mr-1 h-4 w-4" /> Retour aux produits
                </a>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-16">
                {{-- Preview: one drawing per colour, Alpine shows the selected one --}}
                <div class="space-y-4">
                    <div class="relative aspect-[3/4] overflow-hidden border border-gray-200 bg-gray-50">
                        @foreach ($colors as $color)
                            <div class="absolute inset-0 p-10 transition-opacity duration-500" x-show="color === @js($color['name'])" x-transition.opacity @if ($color['name'] !== $initialColor) x-cloak @endif>
                                <x-garment-preview :silhouette="$article->silhouette" :color="$color['hex']" :ink="$marking->ink_hex" :text="$marking->name" :technique="$marking->technique" />
                            </div>
                        @endforeach
                    </div>
                    <p class="text-center text-xs tracking-wider text-gray-400 uppercase">Aperçu illustratif — {{ $marking->technique->label() }}, encre {{ mb_strtolower($marking->ink_color) }}</p>
                </div>

                <div class="space-y-6">
                    <div class="border-b border-gray-200 pb-6">
                        <p class="mb-2 text-xs tracking-wider text-gray-500 uppercase">{{ $article->category->name }} · {{ $article->name }}</p>
                        <h1 class="text-2xl font-light tracking-wide uppercase">{{ $marking->name }}</h1>
                        <p class="mt-4 text-2xl font-light">{{ chf($product->price_cents) }}</p>
                    </div>

                    <p class="text-sm leading-relaxed text-gray-600">{{ $article->description }}</p>

                    <form method="POST" action="{{ route('cart.store') }}" class="space-y-6">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="variant_id" :value="selected?.id">
                        <input type="hidden" name="quantity" :value="qty">

                        <div>
                            <p class="mb-3 text-xs tracking-wider uppercase">Couleur : <span class="font-medium" x-text="color"></span></p>
                            <div class="flex flex-wrap gap-3">
                                @foreach ($colors as $color)
                                    <button type="button" title="{{ $color['name'] }}" @click="pickColor(@js($color['name']))"
                                            :class="color === @js($color['name']) ? 'ring-2 ring-black ring-offset-2' : ''"
                                            class="relative rounded-full">
                                        <span class="block h-10 w-10 rounded-full border-2 border-gray-300 hover:border-gray-500" style="background-color: {{ $color['hex'] }}"></span>
                                        <span x-show="! colorAvailable(@js($color['name']))" class="absolute inset-0 flex items-center justify-center" aria-label="épuisé">
                                            <span class="block h-px w-12 -rotate-45 bg-gray-500"></span>
                                        </span>
                                        <span class="sr-only">{{ $color['name'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <p class="mb-3 text-xs tracking-wider uppercase">Taille</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="v in sizes" :key="v.id">
                                    <button type="button" @click="pickSize(v.size)" :disabled="v.available === 0"
                                            :class="size === v.size ? 'border-black bg-black text-white'
                                                : (v.available > 0 ? 'border-gray-300 hover:border-black' : 'cursor-not-allowed border-gray-200 bg-gray-100 text-gray-400 line-through')"
                                            class="border px-4 py-2 text-sm transition-all" x-text="v.sizeLabel"></button>
                                </template>
                            </div>
                            @error('variant_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <p class="mb-2 text-sm font-medium">
                                <template x-if="! selected"><span class="text-gray-500">Choisissez une taille</span></template>
                                <template x-if="selected && selected.available === 0"><span class="text-red-600">Épuisé dans cette taille</span></template>
                                <template x-if="selected && selected.available > 0 && selected.available <= 3"><span class="text-yellow-600" x-text="`Plus que ${selected.available} en stock`"></span></template>
                                <template x-if="selected && selected.available > 3"><span class="text-green-600">En stock</span></template>
                            </p>
                            <p class="mb-3 text-xs tracking-wider uppercase">Quantité</p>
                            <div class="flex items-center gap-4">
                                <button type="button" @click="qty = Math.max(1, qty - 1)" class="border border-gray-300 p-2 hover:border-black" aria-label="Moins"><x-icon name="minus" class="h-4 w-4" /></button>
                                <span class="w-12 text-center" x-text="qty"></span>
                                <button type="button" @click="qty = Math.min(Math.max(1, available), {{ config('shop.cart.max_quantity_per_line') }}, qty + 1)" class="border border-gray-300 p-2 hover:border-black" aria-label="Plus"><x-icon name="plus" class="h-4 w-4" /></button>
                            </div>
                            @error('quantity')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="border-t border-gray-200 pt-6">
                            <button type="submit" :disabled="! selected || selected.available === 0"
                                    class="inline-flex w-full items-center justify-center bg-black px-8 py-4 text-sm font-medium tracking-wider text-white uppercase hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-40">
                                <x-icon name="bag" class="mr-2 h-4 w-4" /> Ajouter au panier
                            </button>
                        </div>
                    </form>

                    <div class="border-t border-gray-200 pt-6">
                        <details class="group">
                            <summary class="flex cursor-pointer items-center justify-between text-xs tracking-wider uppercase">
                                Détails du produit <x-icon name="chevron-down" class="h-4 w-4 transition group-open:rotate-180" />
                            </summary>
                            <dl class="mt-4 space-y-2 text-sm text-gray-600">
                                <div><dt class="inline">Matière :</dt> <dd class="inline">{{ $article->material }}</dd></div>
                                <div><dt class="inline">Marquage :</dt> <dd class="inline">{{ $marking->technique->label() }}, encre {{ mb_strtolower($marking->ink_color) }}</dd></div>
                                <div><dt class="inline">Référence :</dt> <dd class="inline" x-text="selected ? @js($article->variants->pluck('sku', 'id'))[selected.id] : '—'"></dd></div>
                            </dl>
                        </details>
                    </div>
                    <div class="border-t border-gray-200 pt-6">
                        <details class="group">
                            <summary class="flex cursor-pointer items-center justify-between text-xs tracking-wider uppercase">
                                Livraison &amp; retours <x-icon name="chevron-down" class="h-4 w-4 transition group-open:rotate-180" />
                            </summary>
                            <div class="mt-4 space-y-2 text-sm text-gray-600">
                                <p>Livraison {{ chf(config('shop.shipping.fee_cents')) }}, offerte dès {{ chf(config('shop.shipping.free_from_cents')) }}</p>
                                <p>Délai de livraison : 2 à 4 jours ouvrés en Suisse</p>
                                <p>Retours gratuits sous 30 jours</p>
                            </div>
                        </details>
                    </div>
                </div>
            </div>

            {{-- The two sides of the many-to-many --}}
            @foreach ([
                ['Ce vêtement existe aussi avec', $sameArticle],
                ['« '.$marking->name.' » existe aussi sur', $sameMarking],
            ] as [$heading, $related])
                @if ($related->isNotEmpty())
                    <section class="mt-20">
                        <x-eyebrow as="h2" class="mb-8">{{ $heading }}</x-eyebrow>
                        <div class="grid grid-cols-2 gap-px bg-gray-200 md:grid-cols-4">
                            @foreach ($related as $other)
                                <x-product-card :product="$other" :available="$catalog->availabilityOf($other)" />
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        </div>
    </div>
</x-layouts.shop>
