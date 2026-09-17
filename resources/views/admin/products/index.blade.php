<x-layouts.admin title="Produits">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <x-eyebrow>Produits vendables</x-eyebrow>
            <p class="mt-3 text-sm text-gray-500">Un produit = un article + un marquage. Sa disponibilité est calculée, jamais stockée.</p>
        </div>
        @can('create', App\Models\Product::class)
            <a href="{{ route('admin.products.create') }}" class="btn-primary px-6"><x-icon name="plus" class="mr-2 h-4 w-4" /> Nouveau produit</a>
        @endcan
    </div>

    <x-admin.table :headers="['Marquage', 'Article', 'Prix', ['Unités / pièce', 'hidden 2xl:table-cell'], 'Vendables', ['Vendus', 'hidden 2xl:table-cell'], 'Statut', '']">
        @foreach ($products as $product)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm font-medium">{{ $product->marking->name }}</td>
                <td class="px-6 py-4 text-sm">{{ $product->article->name }}</td>
                <td class="px-6 py-4 text-sm">{{ chf($product->price_cents) }}</td>
                <td class="px-6 py-4 text-sm hidden 2xl:table-cell">{{ $product->units_per_item }}</td>
                <td class="px-6 py-4 text-sm" title="min(stock textile toutes variantes, capacité du marquage) — la capacité est partagée entre les variantes">
                    <x-stock-status :available="$availability[$product->id]" class="text-xs" /> <span class="text-gray-500">({{ $availability[$product->id] }})</span>
                </td>
                <td class="px-6 py-4 text-sm hidden 2xl:table-cell">{{ (int) $product->order_lines_sum_quantity }}</td>
                <td class="px-6 py-4">
                    <span @class(['border px-3 py-1 text-xs tracking-wider uppercase', 'border-green-200 bg-green-50 text-green-700' => $product->is_active, 'border-gray-200 text-gray-500' => ! $product->is_active])>{{ $product->is_active ? 'Actif' : 'Inactif' }}</span>
                </td>
                <td class="px-6 py-4 text-right">
                    @can('update', $product)
                        <a href="{{ route('admin.products.edit', $product) }}" class="inline-flex p-2 text-gray-400 hover:text-black" aria-label="Modifier"><x-icon name="pencil" class="h-4 w-4" /></a>
                    @endcan
                </td>
            </tr>
        @endforeach
    </x-admin.table>
</x-layouts.admin>
