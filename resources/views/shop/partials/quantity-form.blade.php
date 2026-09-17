{{-- − / + buttons: each submits the new quantity (0 removes the line). --}}
<form method="POST" action="{{ route('cart.update', $item->key) }}" class="flex items-center border border-gray-300">
    @csrf @method('PATCH')
    <button name="quantity" value="{{ $item->quantity - 1 }}" class="flex h-8 w-8 items-center justify-center hover:bg-gray-100" aria-label="Retirer un">
        <x-icon name="minus" class="h-3 w-3" />
    </button>
    <span class="w-12 border-x border-gray-300 text-center text-sm leading-8 font-medium">{{ $item->quantity }}</span>
    <button name="quantity" value="{{ $item->quantity + 1 }}" class="flex h-8 w-8 items-center justify-center hover:bg-gray-100 disabled:opacity-30"
            @disabled($item->quantity >= $item->available || $item->quantity >= config('shop.cart.max_quantity_per_line')) aria-label="Ajouter un">
        <x-icon name="plus" class="h-3 w-3" />
    </button>
</form>
