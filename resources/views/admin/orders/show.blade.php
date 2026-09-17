<x-layouts.admin :title="'Commande '.$order->reference">
    <a href="{{ route('admin.orders.index') }}" class="mb-6 inline-flex items-center text-xs tracking-wider text-gray-500 uppercase hover:text-black">
        <x-icon name="arrow-left" class="mr-2 h-3 w-3" /> Commandes
    </a>

    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <h2 class="text-2xl font-light tracking-wider">{{ $order->reference }}</h2>
            <x-admin.status-badge :status="$order->status" />
        </div>
        @if (! $order->isCancelled())
            @can('cancel', $order)
                <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" x-data
                      @submit="if (! confirm('Annuler la commande et remettre le stock en place ?')) $event.preventDefault()">
                    @csrf
                    <button class="border border-red-300 px-6 py-3 text-xs tracking-wider text-red-700 uppercase hover:bg-red-50">Annuler la commande</button>
                </form>
            @endcan
        @endif
    </div>

    <div class="space-y-8">
        <div class="space-y-8">
            <x-admin.table :headers="['Produit', 'Variante', 'Prix', 'Qté', 'Marquage consommé', 'Total']">
                @foreach ($order->lines as $line)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium whitespace-normal">{{ $line->product_name }}</td>
                        <td class="px-6 py-4 text-sm">{{ $line->variant_label }} <span class="hidden font-mono text-xs text-gray-500 2xl:inline">{{ $line->variant->sku }}</span></td>
                        <td class="px-6 py-4 text-sm">{{ chf($line->unit_price_cents) }}</td>
                        <td class="px-6 py-4 text-sm">{{ $line->quantity }}</td>
                        <td class="px-6 py-4 text-sm">{{ $line->marking_units > 0 ? $line->marking_units.' u.' : 'à la demande' }}</td>
                        <td class="px-6 py-4 text-sm">{{ chf($line->line_total_cents) }}</td>
                    </tr>
                @endforeach
            </x-admin.table>

            <section>
                <h3 class="eyebrow mb-4">Mouvements de stock liés</h3>
                <x-admin.table :headers="['Composant', 'Motif', 'Δ', 'Stock après']">
                    @foreach ($movements as $movement)
                        <tr>
                            <td class="px-6 py-3 text-sm">
                                @if ($movement->stockable instanceof App\Models\ArticleVariant)
                                    Textile · {{ $movement->stockable->sku }}
                                @else
                                    Marquage · {{ $movement->stockable->name }}
                                @endif
                            </td>
                            <td class="px-6 py-3 text-sm">{{ $movement->reason->label() }}</td>
                            <td @class(['px-6 py-3 text-sm', 'text-green-700' => $movement->delta > 0, 'text-red-600' => $movement->delta < 0])>{{ sprintf('%+d', $movement->delta) }}</td>
                            <td class="px-6 py-3 text-sm">{{ $movement->stock_after }}</td>
                        </tr>
                    @endforeach
                </x-admin.table>
            </section>
        </div>

        <aside class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <div class="border border-gray-200 bg-white p-6 text-sm">
                <h3 class="eyebrow mb-4">Client</h3>
                <p class="font-medium">{{ $order->customerName() }}</p>
                <p class="text-gray-600">{{ $order->email }}</p>
                @if ($order->phone)<p class="text-gray-600">{{ $order->phone }}</p>@endif
                <p class="mt-4 text-gray-600">{{ $order->address_line }}<br>{{ $order->postal_code }} {{ $order->city }}, {{ $order->country }}</p>
            </div>
            <div class="space-y-2 border border-gray-200 bg-white p-6 text-sm">
                <h3 class="eyebrow mb-4">Montants</h3>
                <div class="flex justify-between"><span class="text-gray-500">Sous-total</span><span>{{ chf($order->subtotal_cents) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Livraison</span><span>{{ $order->shipping_cents === 0 ? 'Offerte' : chf($order->shipping_cents) }}</span></div>
                <div class="flex justify-between border-t border-gray-200 pt-2 text-base"><span>Total</span><span class="font-light">{{ chf($order->total_cents) }}</span></div>
            </div>
            <div class="border border-gray-200 bg-white p-6 text-sm text-gray-600">
                <h3 class="eyebrow mb-4">Historique</h3>
                <p>Passée le {{ $order->created_at?->format('d.m.Y à H:i') }}</p>
                @if ($order->cancelled_at)
                    <p>Annulée le {{ $order->cancelled_at->format('d.m.Y à H:i') }} par {{ $order->canceller?->name ?? '—' }}</p>
                @endif
            </div>
        </aside>
    </div>
</x-layouts.admin>
