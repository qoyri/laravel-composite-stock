<x-layouts.admin title="Mouvements de stock">
    <div class="mb-8">
        <x-eyebrow>Journal des mouvements</x-eyebrow>
        <p class="mt-3 text-sm text-gray-500">Chaque vente, annulation et ajustement, sur les deux types de composants.</p>
    </div>

    <x-admin.table :headers="['Date', 'Composant', 'Motif', 'Δ', 'Après', 'Commande', ['Par', 'hidden 2xl:table-cell']]">
        @foreach ($movements as $movement)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-3 text-sm whitespace-nowrap text-gray-500">{{ $movement->created_at?->format('d.m.Y H:i') }}</td>
                <td class="px-6 py-3 text-sm">
                    @if ($movement->stockable instanceof App\Models\ArticleVariant)
                        <span class="mr-2 border border-gray-200 px-1.5 py-0.5 text-[0.625rem] text-gray-500 uppercase">Textile</span>
                        {{ $movement->stockable->article->name }} · {{ $movement->stockable->label() }}
                    @else
                        <span class="mr-2 border border-gray-200 px-1.5 py-0.5 text-[0.625rem] text-gray-500 uppercase">Marquage</span>
                        {{ $movement->stockable->name }}
                    @endif
                </td>
                <td class="px-6 py-3 text-sm">{{ $movement->reason->label() }}{{ $movement->note ? ' · '.$movement->note : '' }}</td>
                <td @class(['px-6 py-3 text-sm', 'text-green-700' => $movement->delta > 0, 'text-red-600' => $movement->delta < 0])>{{ sprintf('%+d', $movement->delta) }}</td>
                <td class="px-6 py-3 text-sm">{{ $movement->stock_after }}</td>
                <td class="px-6 py-3 text-sm">
                    @if ($movement->order)<a href="{{ route('admin.orders.show', $movement->order) }}" class="hover:underline">{{ $movement->order->reference }}</a>@endif
                </td>
                <td class="px-6 py-3 text-sm text-gray-500 hidden 2xl:table-cell">{{ $movement->user?->name ?? 'Boutique' }}</td>
            </tr>
        @endforeach
    </x-admin.table>

    <div class="mt-6">{{ $movements->links('shop.partials.pagination') }}</div>
</x-layouts.admin>
