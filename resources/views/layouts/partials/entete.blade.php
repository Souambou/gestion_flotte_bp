<header class="sans-impression sticky top-0 z-20 border-b border-ardoise-200 bg-white/90 backdrop-blur">
    <div class="flex items-center gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <button type="button" @click="menuMobile = !menuMobile"
                class="rounded-lg p-2 text-ardoise-600 hover:bg-ardoise-100 lg:hidden" aria-label="Ouvrir le menu">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <div class="min-w-0 flex-1">
            <h1 class="truncate text-lg font-bold text-ardoise-900">@yield('titre', 'Tableau de bord')</h1>
            @hasSection('sous-titre')
                <p class="hidden truncate text-sm text-ardoise-500 sm:block">@yield('sous-titre')</p>
            @endif
        </div>

        @can('reservations.creer')
            <a href="{{ route('reservations.create') }}" class="btn-primaire !px-3 sm:!px-4"
               title="Nouvelle demande de réservation">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
                </svg>
                <span class="hidden sm:inline">Nouvelle demande</span>
            </a>
        @endcan

        {{-- Cloche de notifications --}}
        <div x-data="clocheNotifications()" x-init="charger()" class="relative">
            <button type="button" @click="ouvert = !ouvert"
                    class="relative rounded-lg p-2 text-ardoise-600 hover:bg-ardoise-100" aria-label="Notifications">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span x-show="compteur > 0" x-cloak x-text="compteur"
                      class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-petro-400 px-1 text-[11px] font-bold text-petro-900"></span>
            </button>

            <div x-show="ouvert" x-cloak @click.outside="ouvert = false"
                 class="absolute right-0 mt-2 w-[min(20rem,calc(100vw-2rem))] overflow-hidden rounded-xl2 border border-ardoise-200 bg-white shadow-lg">
                <div class="flex items-center justify-between border-b border-ardoise-100 px-4 py-3">
                    <p class="text-sm font-semibold">Notifications</p>
                    <form method="POST" action="{{ route('notifications.tout-lire') }}">
                        @csrf
                        <button class="text-xs font-medium text-petro-600 hover:underline">Tout marquer comme lu</button>
                    </form>
                </div>
                <template x-if="items.length === 0">
                    <p class="px-4 py-6 text-center text-sm text-ardoise-500">Rien de nouveau pour le moment.</p>
                </template>
                <template x-for="item in items" :key="item.id">
                    <a :href="item.lien" class="block border-b border-ardoise-100 px-4 py-3 hover:bg-ardoise-50">
                        <p class="text-sm font-semibold text-ardoise-800" x-text="item.titre"></p>
                        <p class="text-xs text-ardoise-500" x-text="item.message"></p>
                        <p class="mt-1 text-[11px] text-ardoise-400" x-text="item.date"></p>
                    </a>
                </template>
                <a href="{{ route('notifications.index') }}"
                   class="block px-4 py-3 text-center text-sm font-medium text-petro-700 hover:bg-ardoise-50">
                    Voir toutes les notifications
                </a>
            </div>
        </div>

        {{-- Menu utilisateur --}}
        <div x-data="{ ouvert: false }" class="relative">
            <button type="button" @click="ouvert = !ouvert" class="flex items-center gap-2 rounded-lg p-1 hover:bg-ardoise-100">
                @if(auth()->user()->photo_url)
                    <img src="{{ auth()->user()->photo_url }}" alt="" class="h-9 w-9 rounded-full object-cover">
                @else
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-petro-700 text-sm font-bold text-white">
                        {{ auth()->user()->initiales }}
                    </span>
                @endif
            </button>

            <div x-show="ouvert" x-cloak @click.outside="ouvert = false"
                 class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl2 border border-ardoise-200 bg-white shadow-lg">
                <div class="border-b border-ardoise-100 px-4 py-3">
                    <p class="text-sm font-semibold">{{ auth()->user()->nom_complet }}</p>
                    <p class="truncate text-xs text-ardoise-500">{{ auth()->user()->email }}</p>
                </div>
                <a href="{{ route('profil.edit') }}" class="block px-4 py-2.5 text-sm hover:bg-ardoise-50">Mon profil</a>
                <a href="{{ route('profil.mot-de-passe') }}" class="block px-4 py-2.5 text-sm hover:bg-ardoise-50">Mot de passe</a>
                <form method="POST" action="{{ route('deconnexion') }}"
                      data-confirmer="Vous allez être déconnecté de la plateforme."
                      data-confirmer-titre="Se déconnecter ?"
                      data-confirmer-bouton="Me déconnecter">
                    @csrf
                    <button class="w-full px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50">Se déconnecter</button>
                </form>
            </div>
        </div>
    </div>
</header>

@push('scripts')
<script>
    function clocheNotifications() {
        return {
            ouvert: false,
            compteur: 0,
            items: [],
            charger() {
                fetch('{{ route('notifications.compteur') }}', { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(d => { this.compteur = d.non_lues; this.items = d.dernieres; })
                    .catch(() => {});
                setTimeout(() => this.charger(), 60000);
            },
        };
    }
</script>
@endpush
