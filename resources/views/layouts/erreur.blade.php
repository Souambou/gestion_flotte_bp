<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titre', 'Erreur') · Bénin Pétro</title>
    <link rel="icon" href="{{ asset('images/logo-benin-petro-fonce.png') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="flex h-full items-center justify-center bg-ardoise-50 px-4">
    <div class="w-full max-w-lg">
        <div class="mb-8 flex items-center justify-center gap-2">
            <img src="{{ asset('images/logo-benin-petro-fonce.png') }}" alt="Bénin Pétro"
                 width="780" height="186" class="h-8 w-auto shrink-0 object-contain">
        </div>

        <div class="carte p-8 text-center">
            <p class="font-display text-5xl font-extrabold text-petro-700">@yield('code')</p>
            <h1 class="mt-4 font-display text-lg font-bold text-ardoise-900">@yield('message')</h1>
            <p class="mt-2 text-sm leading-relaxed text-ardoise-500">@yield('detail')</p>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primaire">Retour au tableau de bord</a>
                @else
                    <a href="{{ route('connexion') }}" class="btn-primaire">Se connecter</a>
                @endauth
                <a href="javascript:history.back()" class="btn-secondaire">Page précédente</a>
            </div>
        </div>

        <p class="mt-6 text-center text-xs text-ardoise-400">
            Bénin Pétro SA — Plateforme de réservation et de gestion de flotte
        </p>
    </div>
</body>
</html>
