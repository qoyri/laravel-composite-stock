<x-layouts.shop>
    {{-- Hero --}}
    <section class="relative h-[70vh] min-h-[500px] overflow-hidden bg-gray-100">
        <div class="absolute inset-0 bg-gradient-to-b from-transparent to-black/20"></div>
        <div class="pointer-events-none absolute inset-y-0 right-0 hidden w-1/2 items-center justify-center opacity-90 lg:flex">
            <div class="h-[55%] w-[55%] -rotate-6">
                <x-garment-preview :silhouette="\App\Enums\Silhouette::TShirt" color="#F7F7F2" ink="#1a1a1a" text="J'habite chez mon chat" />
            </div>
        </div>
        <div class="relative flex h-full items-center justify-center lg:justify-start lg:pl-[12%]">
            <div class="z-10 px-4 text-center lg:text-left">
                <h1 class="mb-4 text-5xl font-light tracking-wider text-gray-900 md:text-6xl">ARCHIE COOL</h1>
                <p class="mb-8 text-lg font-light tracking-wide text-gray-700 md:text-xl">T-shirts personnalisés avec une touche d'humour</p>
                <a href="{{ route('markings') }}" class="inline-block bg-black px-8 py-3 text-sm tracking-widest text-white uppercase hover:bg-gray-800">Créer maintenant</a>
            </div>
        </div>
    </section>

    {{-- Category grid: 6 tiles --}}
    <section class="mx-auto max-w-screen-2xl px-6 py-16">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
            @php
                $tileArt = [
                    'homme' => [\App\Enums\Silhouette::Hoodie, '#1F2A44', '#F3EEE3', 'Fondue un jour'],
                    'femme' => [\App\Enums\Silhouette::TShirt, '#E8C4C4', '#111111', 'Café d\'abord'],
                    'enfant' => [\App\Enums\Silhouette::Bodysuit, '#9EC5E8', '#FFFFFF', 'Mini Archie'],
                    'accessoires' => [\App\Enums\Silhouette::ToteBag, '#EDE6D6', '#111111', 'Raclette'],
                ];
            @endphp
            @foreach ($categories as $category)
                <a href="{{ route('catalog', ['categorie' => $category->slug]) }}" class="group relative aspect-[3/4] overflow-hidden bg-gray-100">
                    @isset($tileArt[$category->slug])
                        @php [$sil, $bg, $ink, $txt] = $tileArt[$category->slug]; @endphp
                        <div class="absolute inset-0 p-16 transition-transform duration-500 group-hover:scale-105">
                            <x-garment-preview :silhouette="$sil" :color="$bg" :ink="$ink" :text="$txt" />
                        </div>
                    @endisset
                    <div class="absolute inset-0 bg-gradient-to-b from-transparent to-black/30 transition-all group-hover:to-black/40"></div>
                    <div class="absolute bottom-0 left-0 p-8">
                        <h2 class="text-2xl font-light tracking-wider text-white uppercase">{{ $category->name }}</h2>
                        <p class="mt-2 text-sm text-white/80">{{ $category->tagline }}</p>
                    </div>
                </a>
            @endforeach

            <a href="{{ route('markings') }}" class="group relative aspect-[3/4] overflow-hidden bg-gray-900">
                <div class="absolute inset-0 bg-gradient-to-b from-transparent to-black/30 transition-all group-hover:to-black/40"></div>
                <div class="absolute inset-0 flex items-center justify-center text-center">
                    <div>
                        <h2 class="mb-2 text-2xl font-light tracking-wider text-white uppercase">Personnalisation</h2>
                        <p class="text-sm text-white/80">Broderie, flocage, sérigraphie</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('about') }}" class="group relative aspect-[3/4] overflow-hidden bg-gray-100">
                <div class="absolute inset-0 bg-gradient-to-b from-transparent to-black/30 transition-all group-hover:to-black/40"></div>
                <div class="absolute bottom-0 left-0 p-8">
                    <h2 class="text-2xl font-light tracking-wider text-white uppercase">Le Magasin</h2>
                    <p class="mt-2 text-sm text-white/80">Notre histoire</p>
                </div>
            </a>
        </div>
    </section>

    {{-- New in --}}
    @if ($featured->isNotEmpty())
        <section class="mx-auto max-w-screen-2xl px-6 pb-16">
            <div class="mb-8 flex items-end justify-between">
                <x-eyebrow as="h2">Nouveautés</x-eyebrow>
                <a href="{{ route('catalog') }}" class="text-xs tracking-wider text-gray-500 uppercase hover:text-black">Tout voir</a>
            </div>
            <div class="grid grid-cols-2 gap-px bg-gray-200 md:grid-cols-4">
                @foreach ($featured as $product)
                    <x-product-card :product="$product" :available="$catalog->availabilityOf($product)" />
                @endforeach
            </div>
        </section>
    @endif

    <section class="bg-gray-50 py-20">
        <div class="mx-auto grid max-w-screen-xl grid-cols-1 gap-12 px-6 md:grid-cols-3">
            <div class="text-center">
                <h3 class="mb-3 text-sm font-medium tracking-widest uppercase">Qualité premium</h3>
                <p class="text-sm leading-relaxed text-gray-600">Coton biologique certifié, marquages résistants aux lavages.</p>
            </div>
            <div class="text-center">
                <h3 class="mb-3 text-sm font-medium tracking-widest uppercase">Livraison rapide</h3>
                <p class="text-sm leading-relaxed text-gray-600">Expédition sous 24 h, livraison offerte dès {{ chf(config('shop.shipping.free_from_cents')) }} en Suisse.</p>
            </div>
            <div class="text-center">
                <h3 class="mb-3 text-sm font-medium tracking-widest uppercase">Design unique</h3>
                <p class="text-sm leading-relaxed text-gray-600">Chaque marquage se décline sur plusieurs vêtements : choisissez le vôtre.</p>
            </div>
        </div>
    </section>
</x-layouts.shop>
