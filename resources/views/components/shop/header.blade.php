{{-- Main header, hidden on scroll and replaced by a compact sticky bar (as in the original). --}}
<div x-data="{ scrolled: false, mobileNav: false }" @scroll.window.throttle.50ms="scrolled = window.scrollY > 100">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto max-w-screen-2xl">
            <div class="border-b border-gray-200 px-6 py-2 text-center text-xs text-gray-600 sm:text-left">
                Livraison gratuite dès {{ chf(config('shop.shipping.free_from_cents')) }} en Suisse
            </div>

            <div class="px-6 py-6">
                <div class="flex flex-col items-center space-y-4">
                    <a href="{{ route('home') }}" class="hover:opacity-80">
                        <x-logo size="lg" />
                    </a>

                    <button type="button" class="text-xs tracking-wider uppercase md:hidden" @click="mobileNav = ! mobileNav" :aria-expanded="mobileNav">
                        <x-icon name="menu" class="inline h-4 w-4" /> Menu
                    </button>

                    <nav :class="mobileNav ? 'flex' : 'hidden'" class="flex-col items-center gap-4 md:flex md:flex-row md:gap-8" aria-label="Navigation principale">
                        @foreach ($categories as $category)
                            <a href="{{ route('catalog', ['categorie' => $category->slug]) }}"
                               @class([
                                   'text-sm font-medium tracking-wide uppercase transition-all duration-300 hover:scale-105 hover:text-gray-600',
                                   'text-gray-900' => request('categorie') !== $category->slug,
                                   'underline underline-offset-8' => request('categorie') === $category->slug,
                               ])>{{ $category->name }}</a>
                        @endforeach
                        <a href="{{ route('markings') }}" class="text-sm font-medium tracking-wide text-gray-900 uppercase transition-all duration-300 hover:scale-105 hover:text-gray-600">Personnaliser</a>
                        <a href="{{ route('about') }}" class="text-sm font-medium tracking-wide text-gray-900 uppercase transition-all duration-300 hover:scale-105 hover:text-gray-600">Le Magasin</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <div class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-screen-2xl items-center px-6 py-4">
            @include('components.shop.search')
            @include('components.shop.bag-button', ['class' => 'ml-8'])
        </div>
    </div>

    <div x-cloak x-show="scrolled"
         x-transition:enter="transition duration-300" x-transition:enter-start="-translate-y-full opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="-translate-y-full opacity-0"
         class="fixed inset-x-0 top-0 z-40 border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-screen-2xl items-center justify-between px-6 py-3">
            <a href="{{ route('home') }}" class="hidden shrink-0 sm:block"><x-logo size="sm" /></a>
            <div class="mx-4 flex-1 sm:mx-8">@include('components.shop.search')</div>
            @include('components.shop.bag-button', ['class' => ''])
        </div>
    </div>
</div>
