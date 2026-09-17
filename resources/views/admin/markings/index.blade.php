<x-layouts.admin title="Marquages">
    <div class="mb-8 flex items-center justify-between">
        <x-eyebrow>Marquages et fournitures</x-eyebrow>
        @can('create', App\Models\Marking::class)
            <a href="{{ route('admin.markings.create') }}" class="btn-primary px-6"><x-icon name="plus" class="mr-2 h-4 w-4" /> Nouveau marquage</a>
        @endcan
    </div>
    @error('delta')<p class="mb-4 text-sm text-red-600">{{ $message }}</p>@enderror

    <x-admin.table :headers="['Marquage', 'Technique', ['Encre', 'hidden 2xl:table-cell'], 'Stock', 'Ajuster', ['Produits', 'hidden 2xl:table-cell'], 'Statut', '']">
        @foreach ($markings as $marking)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm font-medium">{{ $marking->name }}</td>
                <td class="px-6 py-4"><span class="border border-gray-200 px-2 py-1 text-xs text-gray-600 uppercase">{{ $marking->technique->label() }}</span></td>
                <td class="px-6 py-4 text-sm hidden 2xl:table-cell"><span class="mr-2 inline-block h-3 w-3 rounded-full border border-gray-300 align-middle" style="background: {{ $marking->ink_hex }}"></span>{{ $marking->ink_color }}</td>
                <td class="px-6 py-4 text-sm">
                    @if ($marking->is_unlimited)
                        <span class="text-gray-500">Illimité</span>
                    @else
                        <x-stock-status :available="$marking->stock" class="text-xs" /> <span class="text-gray-500">({{ $marking->stock }})</span>
                    @endif
                </td>
                <td class="px-6 py-4">
                    @if (! $marking->is_unlimited)
                        @can('adjustStock', $marking)<x-admin.adjust-stock :action="route('admin.stock.markings.adjust', $marking)" />@endcan
                    @endif
                </td>
                <td class="px-6 py-4 text-sm hidden 2xl:table-cell">{{ $marking->products_count }}</td>
                <td class="px-6 py-4">
                    <span @class(['border px-3 py-1 text-xs tracking-wider uppercase', 'border-green-200 bg-green-50 text-green-700' => $marking->is_active, 'border-gray-200 text-gray-500' => ! $marking->is_active])>{{ $marking->is_active ? 'Actif' : 'Inactif' }}</span>
                </td>
                <td class="px-6 py-4 text-right">
                    <a href="{{ route('admin.markings.edit', $marking) }}" class="inline-flex p-2 text-gray-400 hover:text-black" aria-label="Ouvrir"><x-icon name="pencil" class="h-4 w-4" /></a>
                </td>
            </tr>
        @endforeach
    </x-admin.table>
</x-layouts.admin>
