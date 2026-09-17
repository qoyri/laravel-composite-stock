@php
    $fields = [
        ['first_name', 'Prénom', 'text', 'given-name', true],
        ['last_name', 'Nom', 'text', 'family-name', true],
        ['email', 'E-mail', 'email', 'email', true],
        ['phone', 'Téléphone (optionnel)', 'tel', 'tel', false],
        ['address_line', 'Adresse', 'text', 'street-address', true],
        ['postal_code', 'NPA', 'text', 'postal-code', true],
        ['city', 'Localité', 'text', 'address-level2', true],
    ];
@endphp

<x-layouts.shop title="Commande">
    <div class="min-h-screen bg-white py-16">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mb-12">
                <a href="{{ route('cart.index') }}" class="mb-8 inline-flex items-center text-sm tracking-wider text-gray-400 uppercase hover:text-black">
                    <x-icon name="arrow-left" class="mr-2 h-4 w-4" /> Retour au panier
                </a>
                <h1 class="text-4xl font-light tracking-wider text-black uppercase">Commande</h1>
            </div>

            <div class="lg:grid lg:grid-cols-12 lg:gap-16">
                <div class="lg:col-span-7">
                    <form method="POST" action="{{ route('checkout.store') }}" class="space-y-12" x-data="{ sending: false }" @submit="sending = true">
                        @csrf
                        <section>
                            <h2 class="mb-8 text-sm font-medium tracking-widest text-black uppercase">Adresse de livraison</h2>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                @foreach ($fields as [$name, $label, $type, $autocomplete, $required])
                                    <div @class(['md:col-span-2' => in_array($name, ['email', 'address_line'], true)])>
                                        <label for="{{ $name }}" class="label">{{ $label }}</label>
                                        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name) }}" autocomplete="{{ $autocomplete }}"
                                               @required($required) @if ($name === 'postal_code') inputmode="numeric" maxlength="4" @endif
                                               @class(['input-line', 'border-red-400' => $errors->has($name)])>
                                        @error($name)<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                @endforeach
                                <div class="md:col-span-2">
                                    <span class="label">Pays</span>
                                    <p class="py-3 text-sm">Suisse</p>
                                </div>
                            </div>
                        </section>

                        <section>
                            <h2 class="mb-8 text-sm font-medium tracking-widest text-black uppercase">Paiement</h2>
                            <div class="border border-gray-200 p-6">
                                <h3 class="mb-2 text-sm font-medium text-black">Sur facture</h3>
                                <p class="text-xs leading-relaxed text-gray-600">
                                    Projet de démonstration : aucun paiement n'est demandé. La commande est confirmée et le stock décrémenté dès la validation.
                                </p>
                            </div>
                        </section>

                        <button type="submit" :disabled="sending"
                                class="w-full bg-black px-8 py-4 text-sm tracking-widest text-white uppercase hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50">
                            <span x-show="! sending">Valider la commande · {{ chf($summary->totalCents()) }}</span>
                            <span x-cloak x-show="sending" class="inline-flex items-center gap-3">
                                <span class="h-4 w-4 animate-spin rounded-full border border-gray-400 border-t-white"></span> Vérification du stock…
                            </span>
                        </button>
                    </form>
                </div>

                <div class="mt-16 lg:col-span-5 lg:mt-0">
                    @include('shop.partials.summary', ['summary' => $summary, 'showItems' => true])
                </div>
            </div>
        </div>
    </div>
</x-layouts.shop>
