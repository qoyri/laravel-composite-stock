@php $editing = $product->exists; @endphp

<x-layouts.admin :title="$editing ? $product->displayName() : 'Nouveau produit'">
    <a href="{{ route('admin.products.index') }}" class="mb-6 inline-flex items-center text-xs tracking-wider text-gray-500 uppercase hover:text-black">
        <x-icon name="arrow-left" class="mr-2 h-3 w-3" /> Produits
    </a>

    <form method="POST" action="{{ $editing ? route('admin.products.update', $product) : route('admin.products.store') }}"
          class="max-w-xl space-y-6 border border-gray-200 bg-white p-6">
        @csrf
        @if ($editing) @method('PUT') @endif
        <x-eyebrow as="h2">{{ $editing ? 'Modifier le produit' : 'Associer un marquage à un article' }}</x-eyebrow>

        @if ($editing)
            <p class="text-sm"><span class="text-gray-500">Article :</span> {{ $product->article->name }}<br><span class="text-gray-500">Marquage :</span> {{ $product->marking->name }}</p>
        @else
            <x-admin.field name="article_id" label="Article" type="select" required>
                @foreach ($articles as $article)
                    <option value="{{ $article->id }}" @selected(old('article_id') == $article->id)>{{ $article->name }}</option>
                @endforeach
            </x-admin.field>
            <x-admin.field name="marking_id" label="Marquage" type="select" required>
                @foreach ($markings as $marking)
                    <option value="{{ $marking->id }}" @selected(old('marking_id') == $marking->id)>{{ $marking->name }} ({{ $marking->technique->label() }})</option>
                @endforeach
            </x-admin.field>
        @endif

        <div class="grid grid-cols-2 gap-6">
            <x-admin.field name="price" label="Prix (CHF)" type="number" step="0.05" min="1" :value="$editing ? number_format($product->price_cents / 100, 2, '.', '') : null" required />
            <x-admin.field name="units_per_item" label="Unités de marquage / pièce" type="number" min="1" max="20" :value="$product->units_per_item" required />
        </div>
        <p class="text-xs text-gray-500">Les commandes passées gardent les unités réellement consommées : modifier cette valeur n'affecte pas les annulations futures.</p>

        <label class="flex items-center gap-2 text-xs tracking-wider uppercase">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active)) class="accent-black"> En vente
        </label>

        <button class="btn-primary w-full">Enregistrer</button>
    </form>
</x-layouts.admin>
