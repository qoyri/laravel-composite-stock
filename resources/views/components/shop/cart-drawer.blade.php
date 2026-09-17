<div x-cloak x-show="cartOpen" class="relative z-50" role="dialog" aria-modal="true" aria-label="Panier">
    <div x-show="cartOpen" x-transition.opacity.duration.300ms class="fixed inset-0 bg-white/20 backdrop-blur-md" @click="cartOpen = false"></div>

    <div x-show="cartOpen" x-trap.noscroll="cartOpen"
         x-transition:enter="transform transition duration-300 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition duration-300 ease-out" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
         class="fixed top-0 right-0 flex h-full w-full max-w-md flex-col bg-white shadow-xl">

        <div class="flex items-center justify-between border-b border-gray-200 p-6">
            <div>
                <h2 class="eyebrow mb-1">Panier</h2>
                <p class="text-sm font-medium">{{ $summary->count() }} {{ $summary->count() > 1 ? 'articles' : 'article' }}</p>
            </div>
            <button type="button" @click="cartOpen = false" class="inline-flex p-2 text-gray-400 hover:text-black" aria-label="Fermer le panier">
                <x-icon name="x" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            @if ($summary->isEmpty())
                <div class="flex h-full flex-col items-center justify-center px-6 text-center">
                    <x-icon name="bag" class="mx-auto mb-4 h-12 w-12 text-gray-300" />
                    <p class="mb-2 text-xs tracking-wider text-gray-500 uppercase">Panier vide</p>
                    <p class="mb-6 text-sm leading-relaxed text-gray-600">Découvrez notre collection de t-shirts humoristiques</p>
                    <a href="{{ route('catalog') }}" class="btn-primary px-6">Découvrir</a>
                </div>
            @else
                <div class="space-y-6 p-6">
                    @foreach ($summary->items as $item)
                        <div class="border-b border-gray-200 pb-6 last:border-b-0 last:pb-0">
                            <div class="flex space-x-4">
                                <a href="{{ route('products.show', $item->product->slug) }}" class="h-16 w-16 shrink-0 bg-gray-100 hover:opacity-80">
                                    <x-garment-preview :silhouette="$item->product->article->silhouette" :color="$item->variant->color_hex"
                                                       :ink="$item->product->marking->ink_hex" :text="$item->product->marking->name"
                                                       :technique="$item->product->marking->technique" />
                                </a>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('products.show', $item->product->slug) }}" class="block hover:opacity-80">
                                        <h3 class="truncate text-sm font-medium tracking-wide text-gray-900 uppercase">{{ $item->product->marking->name }}</h3>
                                    </a>
                                    <p class="mt-1 text-xs tracking-wider text-gray-500 uppercase">{{ $item->product->article->name }} · {{ chf($item->unitPriceCents()) }}</p>
                                    <p class="mt-1 text-xs tracking-wider text-gray-600 uppercase">Taille : {{ $item->variant->size->label() }} · Couleur : {{ $item->variant->color_name }}</p>
                                    @if ($item->hasProblem())
                                        <p class="mt-1 text-xs text-red-600">{{ $item->available === 0 ? 'Plus disponible' : 'Plus que '.$item->available.' disponible(s)' }}</p>
                                    @endif

                                    <div class="mt-3 flex items-center justify-between">
                                        @include('shop.partials.quantity-form', ['item' => $item, 'compact' => true])
                                        <form method="POST" action="{{ route('cart.destroy', $item->key) }}">
                                            @csrf @method('DELETE')
                                            <button class="p-2 text-gray-400 hover:text-red-600" aria-label="Retirer"><x-icon name="trash" class="h-4 w-4" /></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @unless ($summary->isEmpty())
            <div class="space-y-4 border-t border-gray-200 bg-white p-6">
                <div class="flex items-center justify-between">
                    <span class="text-xs tracking-wider text-gray-500 uppercase">Sous-total</span>
                    <span class="text-lg font-light">{{ chf($summary->subtotalCents) }}</span>
                </div>
                <div class="space-y-3">
                    <a href="{{ route('cart.index') }}" class="btn-secondary w-full">Voir le panier complet</a>
                    <a href="{{ route('checkout.create') }}" class="btn-primary w-full">Commander</a>
                </div>
            </div>
        @endunless
    </div>
</div>
