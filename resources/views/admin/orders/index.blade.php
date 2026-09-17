<x-layouts.admin title="Commandes">
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <x-eyebrow>Commandes</x-eyebrow>
        <nav class="flex gap-2 text-xs tracking-wider uppercase">
            <a href="{{ route('admin.orders.index') }}" @class(['border px-3 py-2', 'border-black bg-black text-white' => ! $status, 'border-gray-300 hover:border-black' => $status])>Toutes</a>
            @foreach ($statuses as $s)
                <a href="{{ route('admin.orders.index', ['statut' => $s->value]) }}" @class(['border px-3 py-2', 'border-black bg-black text-white' => $status === $s, 'border-gray-300 hover:border-black' => $status !== $s])>{{ $s->label() }}s</a>
            @endforeach
        </nav>
    </div>

    <x-admin.table :headers="['Référence', 'Date', 'Client', ['Localité', 'hidden 2xl:table-cell'], 'Articles', 'Total', 'Statut']">
        @forelse ($orders as $order)
            <tr class="transition-colors hover:bg-gray-50">
                <td class="px-6 py-4 text-sm font-medium"><a href="{{ route('admin.orders.show', $order) }}" class="hover:underline">{{ $order->reference }}</a></td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ $order->created_at?->format('d.m.Y H:i') }}</td>
                <td class="px-6 py-4 text-sm">{{ $order->customerName() }}<br><span class="text-xs text-gray-500">{{ $order->email }}</span></td>
                <td class="px-6 py-4 text-sm hidden 2xl:table-cell">{{ $order->postal_code }} {{ $order->city }}</td>
                <td class="px-6 py-4 text-sm">{{ (int) $order->lines_sum_quantity }} <span class="text-gray-500">({{ $order->lines_count }} l.)</span></td>
                <td class="px-6 py-4 text-sm">{{ chf($order->total_cents) }}</td>
                <td class="px-6 py-4"><x-admin.status-badge :status="$order->status" /></td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-6 py-4 text-sm text-gray-500">Aucune commande.</td></tr>
        @endforelse
    </x-admin.table>

    <div class="mt-6">{{ $orders->links('shop.partials.pagination') }}</div>
</x-layouts.admin>
