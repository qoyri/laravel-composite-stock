<form action="{{ route('catalog') }}" method="GET" role="search" class="mx-auto w-full max-w-2xl flex-1">
    <label class="relative block">
        <span class="sr-only">Rechercher un produit</span>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher un produit..."
               class="w-full border border-gray-200 bg-gray-50 py-2.5 pr-4 pl-10 text-sm transition-all focus:border-gray-300 focus:bg-white focus:outline-none">
        <x-icon name="search" class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
    </label>
</form>
