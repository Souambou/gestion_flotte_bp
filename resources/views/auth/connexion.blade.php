<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion · Bénin Pétro</title>
    <link rel="icon" href="{{ asset('images/logo-benin-petro-fonce.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
<div class="flex min-h-full flex-col lg:flex-row">

    {{-- Panneau d'identité : le chevron du logo devient le motif de fond --}}
    <div class="relative flex flex-col justify-between overflow-hidden bg-petro-700 px-8 py-10 lg:w-[45%] lg:px-14 lg:py-14">
        <svg class="pointer-events-none absolute -right-24 top-1/4 h-[420px] w-[420px] text-white/[0.04]"
             viewBox="0 0 16 12" fill="currentColor" aria-hidden="true">
            <path d="M0 4h8V0l8 6-8 6V8H0z"/>
        </svg>

        {{-- « self-start » est indispensable : le panneau est un conteneur flex
             en colonne, qui étirerait sinon l'image sur toute sa largeur tout en
             lui imposant la hauteur fixe — d'où une déformation.
             Les attributs width/height transmettent le rapport d'origine. --}}
        <img src="{{ asset('images/logo-benin-petro-blanc.png') }}" alt="Bénin Pétro"
             width="780" height="186"
             class="relative h-8 w-auto shrink-0 self-start object-contain">

        <div class="relative mt-12 lg:mt-0">
          
            <h1 class="mt-4 font-display text-3xl font-extrabold leading-tight text-white lg:text-4xl">
                Chaque véhicule,<br>chaque chauffeur,<br>chaque trajet.
            </h1>
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/70">
                Réservez un véhicule, suivez l'état de la flotte en temps réel et consultez les rapports
                d'activité générés automatiquement.
            </p>
        </div>

        <p class="relative mt-12 text-xs text-white/40">
            Bénin Pétro SA — Cotonou · Accès réservé aux collaborateurs autorisés
        </p>
    </div>

    {{-- Formulaire --}}
    <div class="flex flex-1 items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm">
            <h2 class="font-display text-2xl font-bold text-ardoise-900">Connexion</h2>
            <p class="mt-1 text-sm text-ardoise-500">Identifiez-vous avec votre compte professionnel.</p>

            @if($errors->any())
                <div class="mt-6 rounded-xl2 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('connexion') }}" class="mt-6 space-y-5"
                  x-data="{ motDePasseVisible: false }">
                @csrf

                {{-- Adresse e-mail --}}
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-ardoise-700">
                        Adresse e-mail professionnelle <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="icone-champ">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6"
                                 viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3 7.5A1.5 1.5 0 014.5 6h15A1.5 1.5 0 0121 7.5v9a1.5 1.5 0 01-1.5 1.5h-15A1.5 1.5 0 013 16.5v-9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.4 7.8l8.6 5.7 8.6-5.7"/>
                            </svg>
                        </span>
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                               required autofocus autocomplete="username"
                               placeholder="prenom.nom@beninpetro.bj"
                               class="champ-icone @error('email') border-red-400 @enderror">
                    </div>
                    @error('email')
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Mot de passe : cadenas a gauche, oeil de visibilite a droite --}}
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-ardoise-700">
                        Mot de passe <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="icone-champ">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6"
                                 viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M6.75 10.5V7.5a5.25 5.25 0 1110.5 0v3"/>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M5.25 10.5h13.5a1.5 1.5 0 011.5 1.5v7.5a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5V12a1.5 1.5 0 011.5-1.5z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14.75v2.5"/>
                            </svg>
                        </span>

                        <input :type="motDePasseVisible ? 'text' : 'password'"
                               type="password" name="password" id="password"
                               required autocomplete="current-password"
                               placeholder="Votre mot de passe"
                               class="champ-icone-action @error('password') border-red-400 @enderror">

                        <button type="button" class="bouton-champ"
                                @click="motDePasseVisible = !motDePasseVisible"
                                :aria-label="motDePasseVisible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                                :aria-pressed="motDePasseVisible"
                                title="Afficher ou masquer le mot de passe">
                            {{-- Oeil ouvert : le mot de passe est masque, un clic l'affiche --}}
                            <svg x-show="! motDePasseVisible" class="h-5 w-5" fill="none" stroke="currentColor"
                                 stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M2.25 12S5.75 5.25 12 5.25 21.75 12 21.75 12 18.25 18.75 12 18.75 2.25 12 2.25 12z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            {{-- Oeil barre : le mot de passe est visible, un clic le masque --}}
                            <svg x-show="motDePasseVisible" x-cloak class="h-5 w-5" fill="none" stroke="currentColor"
                                 stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3.5 3.5l17 17"/>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M10.6 6.6A8.9 8.9 0 0112 6.5c6.25 0 9.75 6.75 9.75 6.75a17 17 0 01-3.2 4.1"/>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M6.4 7.9A16.7 16.7 0 002.25 13.25S5.75 20 12 20a9.4 9.4 0 003.9-.85"/>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9.9 11.2a3 3 0 004.1 4.1"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-ardoise-600">
                    <input type="checkbox" name="remember" value="1"
                           class="rounded border-ardoise-300 text-petro-600 focus:ring-petro-500">
                    Rester connecté sur cet appareil
                </label>

                <button type="submit" class="btn-primaire w-full py-3">Se connecter</button>
            </form>

            <p class="mt-8 text-xs leading-relaxed text-ardoise-500">
                Mot de passe oublié ou compte bloqué ? Contactez l'administrateur de la plateforme
                à <a href="mailto:{{ config('beninpetro.societe.email') }}" class="font-medium text-petro-700 hover:underline">{{ config('beninpetro.societe.email') }}</a>.
            </p>
        </div>
    </div>
</div>
</body>
</html>
