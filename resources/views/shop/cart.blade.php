<x-layouts.shop title="Panier">
    <div class="min-h-screen bg-white py-16">
        @if ($summary->isEmpty())
            <div class="mx-auto max-w-4xl px-6 text-center lg:px-8">
                <x-eyebrow center class="mb-8">Panier</x-eyebrow>
                @include('shop.partials.stock-errors')
                <x-icon name="bag" class="mx-auto mb-6 h-16 w-16 text-gray-300" />
                <p class="mb-2 text-xs tracking-wider text-gray-500 uppercase">Panier vide</p>
                <p class="mx-auto mb-8 max-w-md text-sm leading-relaxed text-gray-600">Découvrez notre collection de t-shirts humoristiques et commencez votre shopping</p>
                <div class="flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="{{ route('catalog') }}" class="btn-primary">Découvrir</a>
                    <a href="{{ route('markings') }}" class="btn-secondary">Personnaliser</a>
                </div>
            </div>
        @else
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="mb-12">
                    <a href="{{ route('catalog') }}" class="mb-6 inline-flex items-center text-xs tracking-wider text-gray-400 uppercase hover:text-black">
                        <x-icon name="arrow-left" class="mr-2 h-3 w-3" /> Continuer le shopping
                    </a>
                    <div class="flex items-center justify-between">
                        <x-eyebrow>Panier ({{ $summary->count() }} {{ $summary->count() > 1 ? 'articles' : 'article' }})</x-eyebrow>
                        <form method="POST" action="{{ route('cart.clear') }}">
                            @csrf @method('DELETE')
                            <button class="text-xs tracking-wider text-gray-400 uppercase hover:text-red-600">Vider</button>
                        </form>
                    </div>
                </div>

                @include('shop.partials.stock-errors')

                <div class="lg:grid lg:grid-cols-12 lg:gap-12">
                    <div class="space-y-8 lg:col-span-8">
                        @foreach ($summary->items as $item)
                            <div class="border-b border-gray-200 pb-8 last:border-b-0 last:pb-0">
                                <div class="flex items-start space-x-6">
                                    <a href="{{ route('products.show', $item->product->slug) }}" class="h-24 w-20 shrink-0 bg-gray-100 p-1 hover:opacity-80">
                                        <x-garment-preview :silhouette="$item->product->article->silhouette" :color="$item->variant->color_hex"
                                                           :ink="$item->product->marking->ink_hex" :text="$item->product->marking->name" :technique="$item->product->marking->technique" />
                                    </a>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <a href="{{ route('products.show', $item->product->slug) }}" class="hover:opacity-80">
                                                    <h3 class="text-sm font-medium tracking-wide text-gray-900 uppercase">{{ $item->product->marking->name }}</h3>
                                                </a>
                                                <p class="mt-1 text-xs tracking-wider text-gray-500 uppercase">{{ $item->product->article->name }} · {{ chf($item->unitPriceCents()) }}</p>
                                                <div class="mt-1 space-y-1 text-xs tracking-wider text-gray-600 uppercase">
                                                    <div>Taille : <span class="font-medium">{{ $item->variant->size->label() }}</span></div>
                                                    <div>Couleur : <span class="font-medium">{{ $item->variant->color_name }}</span></div>
                                                    <div class="text-gray-500">Réf : {{ $item->variant->sku }}</div>
                                                </div>
                                                <div class="mt-2 text-xs">
                                                    @if (! $item->sellable)
                                                        <span class="text-red-600">Ce produit n'est plus proposé : retirez-le pour commander.</span>
                                                    @elseif ($item->quantity > $item->available)
                                                        <span class="text-red-600">{{ $item->available === 0 ? 'Épuisé : retirez-le pour commander.' : 'Plus que '.$item->available.' disponible(s) : réduisez la quantité.' }}</span>
                                                    @else
                                                        <x-stock-status :available="$item->available" />
                                                    @endif
                                                </div>
                                            </div>
                                            <form method="POST" action="{{ route('cart.destroy', $item->key) }}">
                                                @csrf @method('DELETE')
                                                <button class="p-2 text-gray-400 hover:text-red-600" aria-label="Retirer"><x-icon name="trash" class="h-4 w-4" /></button>
                                            </form>
                                        </div>

                                        <div class="mt-4 flex items-center justify-between">
                                            <div class="flex items-center space-x-4">
                                                <span class="text-xs tracking-wider text-gray-500 uppercase">Quantité</span>
                                                @include('shop.partials.quantity-form', ['item' => $item])
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-medium">{{ chf($item->lineTotalCents()) }}</div>
                                                @if ($item->quantity > 1)
                                                    <div class="text-xs tracking-wider text-gray-500 uppercase">{{ chf($item->unitPriceCents()) }} × {{ $item->quantity }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-12 lg:col-span-4 lg:mt-0">
                        @include('shop.partials.summary', ['summary' => $summary, 'showItems' => false])
                        <div class="mt-6 space-y-3">
                            @if ($summary->hasProblems())
                                <p class="text-center text-xs text-red-600">Corrigez les lignes signalées pour commander.</p>
                                <span class="btn-primary w-full cursor-not-allowed opacity-40">Commander</span>
                            @else
                                <a href="{{ route('checkout.create') }}" class="btn-primary w-full">Commander</a>
                            @endif
                            <a href="{{ route('catalog') }}" class="btn-secondary w-full">Continuer</a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.shop>
