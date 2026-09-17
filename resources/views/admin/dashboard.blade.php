<x-layouts.admin title="Tableau de bord">
    <div class="mb-8">
        <x-eyebrow>Vue d'ensemble</x-eyebrow>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <x-admin.stat-card label="Chiffre d'affaires 30 j" :value="chf($stats['revenue30'])" icon="banknote" hint="commandes confirmées" />
        <x-admin.stat-card label="Commandes 30 j" :value="$stats['orders30']" icon="cart" />
        <x-admin.stat-card label="Variantes épuisées" :value="$stats['soldOutVariants']" icon="shirt" :hint="$stats['soldOutVariants'] > 0 ? 'à réassortir' : null" tone="bad" />
        <x-admin.stat-card label="Marquages ≤ 10 unités" :value="$stats['lowMarkings']" icon="palette" :hint="$stats['lowMarkings'] > 0 ? 'à commander' : null" tone="bad" />
    </div>

    <div class="grid grid-cols-1 gap-8 2xl:grid-cols-3">
        <section class="2xl:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="eyebrow">Dernières commandes</h2>
                <a href="{{ route('admin.orders.index') }}" class="text-xs tracking-wider text-gray-500 uppercase hover:text-black">Toutes</a>
            </div>
            <x-admin.table :headers="['Référence', 'Client', 'Date', 'Lignes', 'Total', 'Statut']">
                @foreach ($recentOrders as $order)
                    <tr class="transition-colors hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium"><a href="{{ route('admin.orders.show', $order) }}" class="hover:underline">{{ $order->reference }}</a></td>
                        <td class="px-6 py-4 text-sm">{{ $order->customerName() }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $order->created_at?->format('d.m.Y H:i') }}</td>
                        <td class="px-6 py-4 text-sm">{{ $order->lines_count }}</td>
                        <td class="px-6 py-4 text-sm">{{ chf($order->total_cents) }}</td>
                        <td class="px-6 py-4"><x-admin.status-badge :status="$order->status" /></td>
                    </tr>
                @endforeach
            </x-admin.table>
        </section>

        <section class="space-y-8">
            <div>
                <h2 class="eyebrow mb-4">Textiles en rupture ou presque</h2>
                <div class="divide-y divide-gray-200 border border-gray-200 bg-white">
                    @forelse ($lowVariants as $variant)
                        <a href="{{ route('admin.articles.edit', $variant->article) }}" class="flex items-center justify-between px-4 py-3 text-sm hover:bg-gray-50">
                            <span>{{ $variant->article->name }} <span class="text-gray-500">· {{ $variant->label() }}</span></span>
                            <x-stock-status :available="$variant->stock" class="text-xs" />
                        </a>
                    @empty
                        <p class="px-4 py-3 text-sm text-gray-500">Rien à signaler.</p>
                    @endforelse
                </div>
            </div>
            <div>
                <h2 class="eyebrow mb-4">Fournitures de marquage</h2>
                <div class="divide-y divide-gray-200 border border-gray-200 bg-white">
                    @foreach ($lowMarkings as $marking)
                        <a href="{{ route('admin.markings.edit', $marking) }}" class="flex items-center justify-between px-4 py-3 text-sm hover:bg-gray-50">
                            <span>{{ $marking->name }}</span>
                            <span @class(['text-xs', 'text-red-600' => $marking->stock === 0, 'text-yellow-600' => $marking->stock > 0 && $marking->stock <= 10, 'text-gray-600' => $marking->stock > 10])>{{ $marking->stock }} u.</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
</x-layouts.admin>
