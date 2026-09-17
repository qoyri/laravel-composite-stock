<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Connexion — Admin Archie Cool</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-white font-sans">
    <div class="w-full max-w-sm border border-gray-200 p-8">
        <div class="mb-8 text-center">
            <p class="eyebrow">Admin</p>
            <p class="mt-1 text-lg font-light tracking-wider uppercase">Archie Cool</p>
        </div>

        <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-6">
            @csrf
            <x-admin.field name="email" label="E-mail" type="email" required autofocus autocomplete="username" />
            <x-admin.field name="password" label="Mot de passe" type="password" required autocomplete="current-password" />
            <label class="flex items-center gap-2 text-xs tracking-wider text-gray-600 uppercase">
                <input type="checkbox" name="remember" value="1" class="accent-black"> Rester connecté
            </label>
            <button class="w-full bg-black py-4 text-xs tracking-wider text-white uppercase hover:bg-gray-800">Se connecter</button>
        </form>

        <p class="mt-8 text-center text-xs text-gray-400">
            Démo : admin@archiecool.test ou staff@archiecool.test / password
        </p>
        <p class="mt-4 text-center"><a href="{{ route('home') }}" class="text-xs tracking-wider text-gray-500 uppercase hover:text-black">← Retour à la boutique</a></p>
    </div>
</body>
</html>
