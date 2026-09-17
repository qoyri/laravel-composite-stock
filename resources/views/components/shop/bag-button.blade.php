<button type="button" @click="cartOpen = true"
        class="{{ $class }} relative text-gray-700 transition-all duration-300 hover:scale-110 hover:text-black"
        aria-label="Ouvrir le panier ({{ $cartCount }} article{{ $cartCount > 1 ? 's' : '' }})">
    <x-icon name="bag" />
    @if ($cartCount > 0)
        <span class="absolute -top-2 -right-2 flex h-5 w-5 items-center justify-center rounded-full bg-black text-xs text-white">{{ $cartCount }}</span>
    @endif
</button>
