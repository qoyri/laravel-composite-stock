<div class="sticky top-24 border border-gray-200 p-6">
    <h2 class="eyebrow mb-6">Résumé</h2>

    @if ($showItems)
        <div class="mb-6 max-h-64 space-y-4 overflow-y-auto">
            @foreach ($summary->items as $item)
                <div class="flex items-center space-x-4">
                    <div class="h-16 w-16 shrink-0 bg-gray-100">
                        <x-garment-preview :silhouette="$item->product->article->silhouette" :color="$item->variant->color_hex"
                                           :ink="$item->product->marking->ink_hex" :text="$item->product->marking->name" :technique="$item->product->marking->technique" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="truncate text-sm font-medium text-gray-900">{{ $item->product->displayName() }}</h4>
                        <p class="text-xs text-gray-600">{{ $item->variant->label() }} · Qté : {{ $item->quantity }}</p>
                    </div>
                    <div class="text-sm font-medium text-gray-900">{{ chf($item->lineTotalCents()) }}</div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="space-y-4">
        <div class="flex justify-between text-xs tracking-wider uppercase">
            <span class="text-gray-500">Sous-total ({{ $summary->count() }} {{ $summary->count() > 1 ? 'articles' : 'article' }})</span>
            <span class="font-medium">{{ chf($summary->subtotalCents) }}</span>
        </div>
        <div class="flex justify-between text-xs tracking-wider uppercase">
            <span class="text-gray-500">Livraison</span>
            <span class="font-medium">{{ $summary->shippingCents === 0 ? 'Offerte' : chf($summary->shippingCents) }}</span>
        </div>
        @if ($summary->missingForFreeShippingCents > 0)
            <div class="border border-gray-200 p-3 text-xs tracking-wider text-gray-600 uppercase">
                Ajoutez {{ chf($summary->missingForFreeShippingCents) }} pour la livraison offerte
            </div>
        @endif
        <div class="border-t border-gray-200 pt-4">
            <div class="flex justify-between">
                <span class="text-xs tracking-wider text-gray-500 uppercase">Total TTC</span>
                <span class="text-lg font-light">{{ chf($summary->totalCents()) }}</span>
            </div>
        </div>
    </div>

    <div class="mt-8 space-y-2 border-t border-gray-200 pt-6 text-xs tracking-wider text-gray-500 uppercase">
        <div class="flex items-center"><x-icon name="shield" class="mr-2 h-3 w-3" /> Stock vérifié à la validation</div>
        <div class="flex items-center"><x-icon name="package" class="mr-2 h-3 w-3" /> Livraison 2 à 4 jours</div>
        <div class="flex items-center"><x-icon name="history" class="mr-2 h-3 w-3" /> Retours 30 jours</div>
    </div>
</div>
