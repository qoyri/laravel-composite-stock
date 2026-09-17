<footer class="border-t border-gray-200 bg-white">
    <div class="mx-auto max-w-screen-2xl px-6 py-16">
        <div class="mb-12 grid grid-cols-1 gap-8 md:grid-cols-4">
            <div>
                <x-logo size="sm" class="mb-6" />
                <p class="mb-6 text-sm leading-relaxed text-gray-600">
                    T-shirts personnalisés de qualité premium avec une touche d'humour unique.
                </p>
                <div class="flex space-x-4">
                    @foreach (['facebook', 'instagram', 'twitter'] as $network)
                        <a href="#" class="text-gray-400 hover:text-gray-900" aria-label="{{ ucfirst($network) }}"><x-icon :name="$network" /></a>
                    @endforeach
                </div>
            </div>

            <div>
                <h4 class="mb-4 text-xs font-medium tracking-widest uppercase">Boutique</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="{{ route('catalog', ['categorie' => 'homme']) }}" class="text-gray-600 hover:text-gray-900">Homme</a></li>
                    <li><a href="{{ route('catalog', ['categorie' => 'femme']) }}" class="text-gray-600 hover:text-gray-900">Femme</a></li>
                    <li><a href="{{ route('catalog', ['categorie' => 'enfant']) }}" class="text-gray-600 hover:text-gray-900">Enfant</a></li>
                    <li><a href="{{ route('catalog', ['categorie' => 'accessoires']) }}" class="text-gray-600 hover:text-gray-900">Accessoires</a></li>
                    <li><a href="{{ route('markings') }}" class="text-gray-600 hover:text-gray-900">Personnalisation</a></li>
                </ul>
            </div>

            <div>
                <h4 class="mb-4 text-xs font-medium tracking-widest uppercase">Service client</h4>
                <ul class="space-y-3 text-sm text-gray-600">
                    <li><a href="{{ route('cart.index') }}" class="hover:text-gray-900">Mon panier</a></li>
                    <li>Livraison {{ chf(config('shop.shipping.fee_cents')) }}, offerte dès {{ chf(config('shop.shipping.free_from_cents')) }}</li>
                    <li>Retours sous 30 jours</li>
                </ul>
            </div>

            <div>
                <h4 class="mb-4 text-xs font-medium tracking-widest uppercase">Contact</h4>
                <div class="space-y-3 text-sm text-gray-600">
                    <p>contact@archiecool.test</p>
                    <p>Lun – Ven : 9 h – 18 h</p>
                    <p class="pt-2">Atelier de démonstration<br>Suisse</p>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-8">
            <div class="flex flex-col items-center justify-between space-y-4 md:flex-row md:space-y-0">
                <div class="text-sm text-gray-600">© {{ date('Y') }} Archie Cool. Projet de démonstration.</div>
                <div class="flex items-center space-x-6">
                    <a href="{{ route('about') }}" class="text-sm text-gray-600 hover:text-gray-900">Le magasin</a>
                </div>
            </div>
        </div>
    </div>
</footer>
