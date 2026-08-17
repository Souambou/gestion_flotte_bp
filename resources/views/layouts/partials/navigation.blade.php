@php
    $utilisateur = auth()->user();
@endphp

<a href="{{ route('dashboard') }}" class="mb-8 flex items-center px-2" @click="menuMobile = false">
    <img src="{{ asset('images/logo-benin-petro-blanc.png') }}" alt="Bénin Pétro"
         width="780" height="186" class="h-9 w-auto shrink-0 object-contain">
</a>

<nav class="space-y-6" @click="menuMobile = false">
    <div class="space-y-1">
        <x-lien-nav :href="route('dashboard')" :actif="request()->routeIs('dashboard')" icone="grille">
            Tableau de bord
        </x-lien-nav>

        <x-lien-nav :href="route('reservations.index')" :actif="request()->routeIs('reservations.index', 'reservations.show', 'reservations.create', 'reservations.edit')" icone="calendrier">
            Réservations
            @if($enAttente = \App\Models\Reservation::pourUtilisateur($utilisateur)->where('statut','en_attente')->count())
                <span class="ml-auto rounded-full bg-petro-400 px-2 py-0.5 text-xs font-bold text-petro-900">{{ $enAttente }}</span>
            @endif
        </x-lien-nav>

        <x-lien-nav :href="route('planning.index')" :actif="request()->routeIs('planning.index')" icone="planning">
            Planning de la flotte
        </x-lien-nav>

        @can('deplacements.consulter')
            <x-lien-nav :href="route('deplacements.index')" :actif="request()->routeIs('deplacements.*')" icone="route">
                Déplacements
            </x-lien-nav>
        @endcan

        @can('checklists.remplir')
            <x-lien-nav :href="route('checklists.index')" :actif="request()->routeIs('checklists.*')" icone="controle">
                Contrôle matinal
                @if($aControler = \App\Models\Checklist::vehiculesRestants()->count())
                    <span class="ml-auto rounded-full bg-amber-400 px-2 py-0.5 text-xs font-bold text-amber-950">{{ $aControler }}</span>
                @endif
            </x-lien-nav>
        @endcan
    </div>

    @canany(['vehicules.consulter', 'chauffeurs.consulter', 'maintenances.consulter'])
        <div class="space-y-1">
            <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-white/40">Flotte</p>

            @can('vehicules.consulter')
                <x-lien-nav :href="route('vehicules.index')" :actif="request()->routeIs('vehicules.*')" icone="vehicule">
                    Véhicules
                </x-lien-nav>
            @endcan

            @can('chauffeurs.consulter')
                <x-lien-nav :href="route('chauffeurs.index')" :actif="request()->routeIs('chauffeurs.*')" icone="chauffeur">
                    Chauffeurs
                </x-lien-nav>
            @endcan

            @can('maintenances.consulter')
                <x-lien-nav :href="route('maintenances.index')" :actif="request()->routeIs('maintenances.*')" icone="cle">
                    Maintenance
                </x-lien-nav>
            @endcan
        </div>
    @endcanany

    <div class="space-y-1">
        <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-white/40">Suivi</p>

        <x-lien-nav :href="route('litiges.index')" :actif="request()->routeIs('litiges.*')" icone="alerte">
            Litiges
        </x-lien-nav>

        @can('rapports.consulter')
            <x-lien-nav :href="route('rapports.index')" :actif="request()->routeIs('rapports.*')" icone="graphique">
                Rapports
            </x-lien-nav>

            <x-lien-nav :href="route('avis.index')" :actif="request()->routeIs('avis.*')" icone="etoile">
                Avis internes
            </x-lien-nav>
        @endcan
    </div>

    @canany(['utilisateurs.consulter', 'roles.gerer', 'parametres.gerer', 'agences.gerer'])
        <div class="space-y-1">
            <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-white/40">Administration</p>

            @can('utilisateurs.consulter')
                <x-lien-nav :href="route('utilisateurs.index')" :actif="request()->routeIs('utilisateurs.*')" icone="equipe">
                    Utilisateurs
                </x-lien-nav>
            @endcan

            @can('roles.gerer')
                <x-lien-nav :href="route('roles.index')" :actif="request()->routeIs('roles.*')" icone="bouclier">
                    Rôles & permissions
                </x-lien-nav>
            @endcan

            @can('agences.gerer')
                <x-lien-nav :href="route('agences.index')" :actif="request()->routeIs('agences.*')" icone="site">
                    Sites & agences
                </x-lien-nav>
            @endcan

            @can('parametres.gerer')
                <x-lien-nav :href="route('parametres.index')" :actif="request()->routeIs('parametres.*')" icone="reglage">
                    Paramètres & clés API
                </x-lien-nav>
            @endcan
        </div>
    @endcanany
</nav>

<div class="mt-8 rounded-xl2 bg-white/5 p-4">
    <p class="text-xs text-white/50">Connecté en tant que</p>
    <p class="mt-1 text-sm font-semibold text-white">{{ $utilisateur->nom_complet }}</p>
    <p class="text-xs text-petro-400">{{ $utilisateur->role_libelle }}</p>
</div>
