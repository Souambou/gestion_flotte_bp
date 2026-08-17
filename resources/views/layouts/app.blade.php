<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#01582D">
    <title>@yield('titre', 'Tableau de bord') · Bénin Pétro</title>
    <link rel="icon" href="{{ asset('images/logo-benin-petro-fonce.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full bg-ardoise-50">
<div x-data="{ menuMobile: false }">

    {{-- =========================================================== Navigation
         La barre reste fixe : elle ne défile jamais avec le contenu, et son
         propre défilement interne prend le relais si les entrées débordent. --}}
    <aside class="sans-impression fixed inset-y-0 left-0 z-50 flex w-72 flex-col overflow-y-auto
                  overscroll-contain bg-petro-700 px-4 py-6 transition-transform duration-200
                  lg:translate-x-0"
           :class="menuMobile ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           x-cloak
           aria-label="Navigation principale">
        @include('layouts.partials.navigation')
    </aside>

    {{-- Voile d'arrière-plan, uniquement sur petit écran --}}
    <div x-show="menuMobile" x-cloak x-transition.opacity
         @click="menuMobile = false"
         class="fixed inset-0 z-40 bg-ardoise-900/50 lg:hidden"></div>

    {{-- =========================================================== Contenu
         Décalé de la largeur de la barre à partir de « lg ». --}}
    <div class="flex min-h-full flex-col lg:pl-72">
        @include('layouts.partials.entete')

        <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
            @include('layouts.partials.messages')

            @yield('contenu')
        </main>

        <footer class="sans-impression border-t border-ardoise-200 px-4 py-4 text-xs text-ardoise-500 sm:px-6 lg:px-8">
            Bénin Pétro SA — Plateforme de réservation et de gestion de flotte ·
            Données personnelles traitées conformément aux exigences de l'APDP Bénin.
        </footer>
    </div>
</div>
@stack('scripts')
</body>
</html>
