<x-layouts.shop title="Commande confirmée">
    <div class="min-h-screen bg-white py-16">
        <div class="mx-auto max-w-3xl px-6">
            <div class="mb-12 text-center">
                <x-icon name="check" class="mx-auto mb-6 h-12 w-12 text-accent" />
                <h1 class="mb-4 text-4xl font-light tracking-wider text-black uppercase">Merci {{ $order->first_name }} !</h1>
                <p class="text-sm text-gray-600">
                    Commande <span class="font-medium text-black">{{ $order->reference }}</span>
                    @if ($order->isCancelled()) — <span class="text-red-600">annulée</span> @else confirmée @endif.
                    Un récapitulatif part à {{ $order->email }}.
                </p>
            </div>

            <div class="border border-gray-200 p-6">
                <h2 class="eyebrow mb-6">Récapitulatif</h2>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($order->lines as $line)
                            <tr>
                                <td class="py-3">
                                    <div class="font-medium">{{ $line->product_name }}</div>
                                    <div class="text-xs tracking-wider text-gray-500 uppercase">{{ $line->variant_label }}</div>
                                </td>
                                <td class="py-3 text-center text-gray-600">× {{ $line->quantity }}</td>
                                <td class="py-3 text-right">{{ chf($line->line_total_cents) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-gray-200 text-xs tracking-wider uppercase">
                        <tr><td class="pt-4 text-gray-500" colspan="2">Sous-total</td><td class="pt-4 text-right">{{ chf($order->subtotal_cents) }}</td></tr>
                        <tr><td class="pt-2 text-gray-500" colspan="2">Livraison</td><td class="pt-2 text-right">{{ $order->shipping_cents === 0 ? 'Offerte' : chf($order->shipping_cents) }}</td></tr>
                        <tr class="text-base normal-case tracking-normal"><td class="pt-4" colspan="2">Total</td><td class="pt-4 text-right font-light">{{ chf($order->total_cents) }}</td></tr>
                    </tfoot>
                </table>

                <div class="mt-8 border-t border-gray-200 pt-6 text-sm text-gray-600">
                    <p class="label">Livraison à</p>
                    <p>{{ $order->customerName() }}<br>{{ $order->address_line }}<br>{{ $order->postal_code }} {{ $order->city }}</p>
                </div>
            </div>

            <div class="mt-10 text-center">
                <a href="{{ route('catalog') }}" class="btn-primary">Continuer le shopping</a>
            </div>
        </div>
    </div>
</x-layouts.shop>
