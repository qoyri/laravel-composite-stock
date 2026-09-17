@props(['title'])

@php
    $nav = [
        ['admin.dashboard', 'admin.dashboard', 'Tableau de bord', 'dashboard'],
        ['admin.articles.index', 'admin.articles.*', 'Articles', 'shirt'],
        ['admin.markings.index', 'admin.markings.*', 'Marquages', 'palette'],
        ['admin.products.index', 'admin.products.*', 'Produits', 'tag'],
        ['admin.stock.index', 'admin.stock.*', 'Mouvements de stock', 'history'],
        ['admin.orders.index', 'admin.orders.*', 'Commandes', 'cart'],
    ];
    $user = auth()->user();
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} — Admin Archie Cool</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans" x-data="{ sidebar: false }">
    <div x-cloak x-show="sidebar" @click="sidebar = false" class="fixed inset-0 z-30 bg-black/20 lg:hidden"></div>

    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
           class="fixed top-0 left-0 z-40 flex h-full w-72 flex-col border-r border-gray-200 bg-white transition-transform lg:translate-x-0">
        <div class="border-b border-gray-200 p-6">
            <p class="eyebrow">Admin</p>
            <p class="text-lg font-light tracking-wider uppercase">Archie Cool</p>
        </div>

        <nav class="flex-1 space-y-1 p-4">
            @foreach ($nav as [$route, $pattern, $label, $icon])
                @php $active = request()->routeIs($pattern); @endphp
                <a href="{{ route($route) }}" @class([
                    'flex items-center justify-between px-4 py-3 text-xs tracking-wider uppercase',
                    'bg-black text-white' => $active,
                    'text-gray-600 hover:bg-gray-100 hover:text-black' => ! $active,
                ])>
                    <span class="flex items-center gap-3"><x-icon :name="$icon" class="h-4 w-4" /> {{ $label }}</span>
                    @if ($active)<x-icon name="chevron-right" class="h-3 w-3" />@endif
                </a>
            @endforeach
        </nav>

        <div class="border-t border-gray-200 p-4">
            <p class="text-sm font-medium">{{ $user?->name }}</p>
            <p class="mb-3 text-xs tracking-wider text-gray-500 uppercase">{{ $user?->role->label() }}</p>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="flex w-full items-center gap-2 px-4 py-2 text-xs tracking-wider text-gray-600 uppercase hover:bg-gray-100 hover:text-black">
                    <x-icon name="log-out" class="h-4 w-4" /> Déconnexion
                </button>
            </form>
        </div>
    </aside>

    <div class="lg:pl-72">
        <header class="sticky top-0 z-20 border-b border-gray-200 bg-white">
            <div class="flex items-center justify-between px-6 py-4 lg:px-8">
                <div class="flex items-center gap-4">
                    <button class="lg:hidden" @click="sidebar = true" aria-label="Menu"><x-icon name="menu" /></button>
                    <h1 class="text-xs tracking-[0.2em] text-gray-500 uppercase">{{ $title }}</h1>
                </div>
                <a href="{{ route('home') }}" class="flex items-center gap-2 text-xs tracking-wider text-gray-500 uppercase hover:text-black">
                    Voir le site <x-icon name="external" class="h-3 w-3" />
                </a>
            </div>
        </header>

        <main class="p-6 lg:p-8">
            @if (session('status'))
                <div class="mb-6 flex items-center gap-2 border border-green-200 bg-white p-4 text-sm text-green-700" role="status">
                    <x-icon name="check" class="h-4 w-4" /> {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-6 flex items-center gap-2 border border-orange-200 bg-white p-4 text-sm text-orange-800" role="alert">
                    <x-icon name="alert" class="h-4 w-4" /> {{ session('error') }}
                </div>
            @endif
            {{ $slot }}
        </main>
    </div>
</body>
</html>
