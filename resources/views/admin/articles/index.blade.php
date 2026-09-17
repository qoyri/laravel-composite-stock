<x-layouts.admin title="Articles">
    <div class="mb-8 flex items-center justify-between">
        <x-eyebrow>Articles textiles</x-eyebrow>
        @can('create', App\Models\Article::class)
            <a href="{{ route('admin.articles.create') }}" class="btn-primary px-6"><x-icon name="plus" class="mr-2 h-4 w-4" /> Nouvel article</a>
        @endcan
    </div>

    <x-admin.table :headers="['Article', 'Catégorie', ['Silhouette', 'hidden 2xl:table-cell'], 'Variantes', 'Stock total', 'Produits', 'Statut', '']">
        @foreach ($articles as $article)
            <tr class="transition-colors hover:bg-gray-50">
                <td class="px-6 py-4 text-sm font-medium">{{ $article->name }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $article->category->name }}</td>
                <td class="px-6 py-4 hidden 2xl:table-cell"><span class="border border-gray-200 px-2 py-1 text-xs text-gray-600 uppercase">{{ $article->silhouette->label() }}</span></td>
                <td class="px-6 py-4 text-sm">{{ $article->variants_count }}</td>
                <td class="px-6 py-4 text-sm">{{ (int) $article->variants_sum_stock }}</td>
                <td class="px-6 py-4 text-sm">{{ $article->products_count }}</td>
                <td class="px-6 py-4">
                    <span @class(['border px-3 py-1 text-xs tracking-wider uppercase', 'border-green-200 bg-green-50 text-green-700' => $article->is_active, 'border-gray-200 text-gray-500' => ! $article->is_active])>{{ $article->is_active ? 'Actif' : 'Inactif' }}</span>
                </td>
                <td class="px-6 py-4 text-right">
                    <a href="{{ route('admin.articles.edit', $article) }}" class="inline-flex p-2 text-gray-400 hover:text-black" aria-label="Ouvrir"><x-icon name="pencil" class="h-4 w-4" /></a>
                </td>
            </tr>
        @endforeach
    </x-admin.table>
</x-layouts.admin>
