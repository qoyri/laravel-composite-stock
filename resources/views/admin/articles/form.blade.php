@php
    $editing = $article->exists;
    $canEdit = $editing ? auth()->user()->can('update', $article) : true;
@endphp

<x-layouts.admin :title="$editing ? $article->name : 'Nouvel article'">
    <a href="{{ route('admin.articles.index') }}" class="mb-6 inline-flex items-center text-xs tracking-wider text-gray-500 uppercase hover:text-black">
        <x-icon name="arrow-left" class="mr-2 h-3 w-3" /> Articles
    </a>

    <div class="space-y-8">
        <form method="POST" action="{{ $editing ? route('admin.articles.update', $article) : route('admin.articles.store') }}"
              class="max-w-3xl space-y-6 border border-gray-200 bg-white p-6">
            @csrf
            @if ($editing) @method('PUT') @endif
            <x-eyebrow as="h2">{{ $editing ? 'Fiche article' : 'Nouvel article' }}</x-eyebrow>

            <fieldset @disabled(! $canEdit) class="space-y-6">
                <x-admin.field name="name" label="Nom" :value="$article->name" required />
                <x-admin.field name="slug" label="Slug (auto si vide)" :value="$article->slug" />
                <x-admin.field name="category_id" label="Catégorie" type="select" required>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $article->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </x-admin.field>
                <x-admin.field name="silhouette" label="Silhouette" type="select" required>
                    @foreach ($silhouettes as $silhouette)
                        <option value="{{ $silhouette->value }}" @selected(old('silhouette', $article->silhouette?->value) === $silhouette->value)>{{ $silhouette->label() }}</option>
                    @endforeach
                </x-admin.field>
                <x-admin.field name="material" label="Matière" :value="$article->material" />
                <x-admin.field name="description" label="Description" type="textarea" :value="$article->description" required />
                <label class="flex items-center gap-2 text-xs tracking-wider uppercase">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $article->is_active)) class="accent-black"> Actif
                </label>
            </fieldset>

            @if ($canEdit)
                <button class="btn-primary w-full">Enregistrer</button>
            @else
                <p class="text-xs text-gray-500">Lecture seule : seul un administrateur modifie le catalogue.</p>
            @endif
        </form>

        @if ($editing)
            <div class="space-y-8">
                <section>
                    <h2 class="eyebrow mb-4">Variantes et stock</h2>
                    @error('delta')<p class="mb-4 text-sm text-red-600">{{ $message }}</p>@enderror
                    <x-admin.table :headers="['Couleur', 'Taille', 'SKU', 'Stock', 'Ajuster', '']">
                        @forelse ($article->variants as $variant)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm">
                                    <span class="mr-2 inline-block h-3 w-3 rounded-full border border-gray-300 align-middle" style="background: {{ $variant->color_hex }}"></span>{{ $variant->color_name }}
                                </td>
                                <td class="px-6 py-3 text-sm">{{ $variant->size->label() }}</td>
                                <td class="px-6 py-3 font-mono text-xs text-gray-500">{{ $variant->sku }}</td>
                                <td class="px-6 py-3 text-sm"><x-stock-status :available="$variant->stock" class="text-xs" /> <span class="text-gray-500">({{ $variant->stock }})</span></td>
                                <td class="px-6 py-3">
                                    @can('adjustStock', $variant)
                                        <x-admin.adjust-stock :action="route('admin.stock.variants.adjust', $variant)" />
                                    @endcan
                                </td>
                                <td class="px-6 py-3 text-right">
                                    @if ($canEdit)
                                        <form method="POST" action="{{ route('admin.articles.variants.destroy', [$article, $variant]) }}">
                                            @csrf @method('DELETE')
                                            <button class="p-2 text-gray-400 hover:text-red-600" aria-label="Supprimer la variante"><x-icon name="trash" class="h-4 w-4" /></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-4 text-sm text-gray-500">Aucune variante : ajoutez-en une ci-dessous.</td></tr>
                        @endforelse
                    </x-admin.table>
                </section>

                @if ($canEdit)
                    <form method="POST" action="{{ route('admin.articles.variants.store', $article) }}" class="border border-gray-200 bg-white p-6">
                        @csrf
                        <h2 class="eyebrow mb-6">Ajouter une variante</h2>
                        <div class="grid grid-cols-2 gap-6 md:grid-cols-5">
                            <x-admin.field name="color_name" label="Couleur" required />
                            <x-admin.field name="color_hex" label="Teinte" type="color" value="#111111" />
                            <x-admin.field name="size" label="Taille" type="select" required>
                                @foreach (App\Enums\Size::cases() as $size)
                                    <option value="{{ $size->value }}" @selected(old('size') === $size->value)>{{ $size->label() }}</option>
                                @endforeach
                            </x-admin.field>
                            <x-admin.field name="sku" label="SKU" required />
                            <x-admin.field name="stock" label="Stock initial" type="number" min="0" value="0" required />
                        </div>
                        <button class="btn-secondary mt-6">Ajouter</button>
                    </form>
                @endif

                <section>
                    <h2 class="eyebrow mb-4">Produits (article × marquage)</h2>
                    <div class="divide-y divide-gray-200 border border-gray-200 bg-white">
                        @forelse ($article->products as $product)
                            <a href="{{ $canEdit ? route('admin.products.edit', $product) : route('admin.products.index') }}" class="flex justify-between px-4 py-3 text-sm hover:bg-gray-50">
                                <span>{{ $product->marking->name }}</span><span>{{ chf($product->price_cents) }}</span>
                            </a>
                        @empty
                            <p class="px-4 py-3 text-sm text-gray-500">Aucun marquage proposé sur cet article.</p>
                        @endforelse
                    </div>
                </section>

                @can('delete', $article)
                    <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" x-data @submit="if (! confirm('Supprimer cet article ?')) $event.preventDefault()">
                        @csrf @method('DELETE')
                        <button class="text-xs tracking-wider text-gray-400 uppercase hover:text-red-600">Supprimer l'article</button>
                    </form>
                @endcan
            </div>
        @endif
    </div>
</x-layouts.admin>
