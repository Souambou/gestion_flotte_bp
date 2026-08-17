@extends('layouts.app')
@section('titre', 'Réservations')
@section('sous-titre', $reservations->total().' demande(s)')

@section('contenu')

    {{-- Filtres par statut. Sans filtre, la liste suit l'ordre métier :
         les demandes en attente d'abord, les demandes closes en dernier. --}}
    <div class="mb-5 flex flex-wrap gap-2">
        <a href="{{ route('reservations.index') }}"
           @class(['btn-secondaire', '!bg-petro-700 !text-white !border-petro-700' => ! request('statut')])>
            Toutes
        </a>
        @foreach(\App\Models\Reservation::ORDRE_STATUTS as $cle)
            <a href="{{ route('reservations.index', ['statut' => $cle]) }}"
               @class(['btn-secondaire', '!bg-petro-700 !text-white !border-petro-700' => request('statut') === $cle])>
                {{ \App\Models\Reservation::STATUTS[$cle] }}
                <span class="rounded-full bg-black/10 px-1.5 text-xs">{{ $compteurs[$cle] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <x-carte :padding="false">
        <form method="GET" class="grid gap-3 border-b border-ardoise-100 p-4 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-end">
            <input type="hidden" name="statut" value="{{ request('statut') }}">

            <div class="sm:col-span-2 lg:min-w-[220px] lg:flex-1">
                <label for="q" class="mb-1 block text-xs font-medium text-ardoise-600">Rechercher</label>
                <input type="search" name="q" id="q" value="{{ request('q') }}" class="champ"
                       placeholder="Référence, lieu ou demandeur">
            </div>

            <div class="sm:col-span-2 lg:min-w-[240px]">
                <label for="departement" class="mb-1 block text-xs font-medium text-ardoise-600">Département</label>
                <select name="departement" id="departement" class="champ">
                    <option value="">Tous les départements</option>
                    @foreach($departements as $cle => $libelle)
                        <option value="{{ $cle }}" @selected(request('departement') === $cle)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="debut" class="mb-1 block text-xs font-medium text-ardoise-600">À partir du</label>
                <input type="date" name="debut" id="debut" value="{{ request('debut') }}" class="champ">
            </div>
            <div>
                <label for="fin" class="mb-1 block text-xs font-medium text-ardoise-600">Jusqu'au</label>
                <input type="date" name="fin" id="fin" value="{{ request('fin') }}" class="champ">
            </div>

            <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                <button class="btn-secondaire">Filtrer</button>
                @if(request()->hasAny(['q', 'debut', 'fin', 'departement']))
                    <a href="{{ route('reservations.index', ['statut' => request('statut')]) }}" class="btn-fantome">Réinitialiser</a>
                @endif
            </div>
        </form>

        {{-- ------------------------------------------------- Tableau (écrans larges) --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="tableau">
                <thead>
                <tr>
                    <th>Référence</th>
                    <th>Demandeur</th>
                    <th>Département</th>
                    <th>Trajet</th>
                    <th>Période</th>
                    <th>Véhicule</th>
                    <th>Statut</th>
                    <th class="text-right">Détail</th>
                </tr>
                </thead>
                <tbody>
                @forelse($reservations as $reservation)
                    <tr>
                        <td class="whitespace-nowrap">
                            <span class="font-mono text-xs font-semibold text-petro-700">{{ $reservation->code }}</span>
                            <p class="text-[11px] text-ardoise-400">{{ $reservation->type_deplacement_libelle }}</p>
                        </td>
                        <td>
                            <p class="font-medium">{{ $reservation->demandeur?->nom_complet }}</p>
                            <p class="text-xs text-ardoise-500">{{ $reservation->avec_chauffeur ? 'Avec chauffeur' : 'Sans chauffeur' }}</p>
                        </td>
                        <td class="text-xs text-ardoise-600">{{ $reservation->departement_libelle }}</td>
                        <td><x-trajet :depart="$reservation->lieu_depart" :arrivee="$reservation->lieu_arrivee" compact/></td>
                        <td class="whitespace-nowrap text-ardoise-600">
                            <p>{{ $reservation->date_debut->format('d/m/Y H:i') }}</p>
                            <p class="text-xs text-ardoise-400">{{ $reservation->duree_heures }} h</p>
                        </td>
                        <td class="whitespace-nowrap">
                            @if($reservation->vehicule)
                                <a href="{{ route('vehicules.show', $reservation->vehicule) }}" class="hover:text-petro-700">
                                    {{ $reservation->vehicule?->immatriculation }}
                                </a>
                            @else
                                <span class="text-ardoise-400">À affecter</span>
                            @endif
                        </td>
                        <td><x-badge :statut="$reservation->statut" :libelle="$reservation->statut_libelle"/></td>
                        <td class="text-right">
                            {{-- Œil : accès au détail de la demande --}}
                            <a href="{{ route('reservations.show', $reservation) }}"
                               class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-ardoise-500 transition hover:bg-petro-50 hover:text-petro-700"
                               title="Voir le détail de la demande {{ $reservation->code }}"
                               aria-label="Voir le détail de la demande {{ $reservation->code }}">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M2.25 12S5.75 5.25 12 5.25 21.75 12 21.75 12 18.25 18.75 12 18.75 2.25 12 2.25 12z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <x-vide titre="Aucune réservation trouvée"
                                    message="Ajustez vos filtres, ou créez une nouvelle demande de véhicule."
                                    action="Nouvelle demande" :lien="route('reservations.create')"/>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- ------------------------------------------------- Cartes (mobile) --}}
        <div class="divide-y divide-ardoise-100 md:hidden">
            @forelse($reservations as $reservation)
                <a href="{{ route('reservations.show', $reservation) }}" class="block px-4 py-3 transition hover:bg-ardoise-50">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-xs font-semibold text-petro-700">{{ $reservation->code }}</p>
                            <p class="mt-0.5 truncate text-sm font-medium text-ardoise-900">{{ $reservation->demandeur?->nom_complet }}</p>
                        </div>
                        <x-badge :statut="$reservation->statut" :libelle="$reservation->statut_libelle" class="shrink-0"/>
                    </div>

                    <p class="mt-1.5 truncate text-xs text-ardoise-600">{{ $reservation->trajet }}</p>
                    <p class="mt-0.5 text-xs text-ardoise-500">
                        {{ $reservation->date_debut->format('d/m/Y H:i') }} · {{ $reservation->duree_heures }} h
                        @if($reservation->vehicule) · {{ $reservation->vehicule?->immatriculation }} @endif
                    </p>
                    <p class="mt-0.5 truncate text-[11px] text-ardoise-400">{{ $reservation->departement_libelle }}</p>
                </a>
            @empty
                <x-vide titre="Aucune réservation trouvée"
                        message="Ajustez vos filtres, ou créez une nouvelle demande de véhicule."
                        action="Nouvelle demande" :lien="route('reservations.create')"/>
            @endforelse
        </div>

        @if($reservations->hasPages())
            <div class="border-t border-ardoise-100 p-4">{{ $reservations->links() }}</div>
        @endif
    </x-carte>
@endsection
