@props(['title' => null, 'description' => null])

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — ' : '' }}L'Atelier de l'Humour</title>
    <meta name="description" content="{{ $description ?? 'T-shirts humoristiques personnalisés, brodés, floqués ou sérigraphiés. Livraison en Suisse.' }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans"
      x-data="{ cartOpen: @js(session('cart.opened', false)) }"
      @keydown.escape.window="cartOpen = false">
    <div class="flex min-h-screen flex-col">
        <x-shop.header />

        <main class="animate-fade-in-up flex-1">
            {{ $slot }}
        </main>

        <x-shop.footer />
    </div>

    <x-shop.cart-drawer />
</body>
</html>
