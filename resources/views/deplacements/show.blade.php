@extends('layouts.app')
@section('titre', 'Déplacement '.$deplacement->code)
@section('sous-titre', $deplacement->vehicule?->libelle)

@section('contenu')
    <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
        <div class="space-y-6 lg:col-span-2">

            <x-carte>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <x-badge :statut="$deplacement->statut" :libelle="$deplacement->statut_libelle"/>
                        <p class="mt-3 font-display text-xl font-bold">
                            <x-trajet :depart="$deplacement->reservation?->lieu_depart" :arrivee="$deplacement->reservation?->lieu_arrivee" class="!text-xl"/>
                        </p>
                        <p class="mt-1 text-sm text-ardoise-500">
                            Réservation <a href="{{ route('reservations.show', $deplacement->reservation) }}" class="font-medium text-petro-700 hover:underline">{{ $deplacement->reservation?->code }}</a>
                            · {{ $deplacement->reservation?->demandeur->nom_complet }}
                        </p>
                    </div>
                    <a href="{{ route('reservations.show', $deplacement->reservation) }}" class="btn-secondaire sans-impression">Voir la réservation</a>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-4 border-t border-ardoise-100 pt-6 sm:grid-cols-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-ardoise-500">Départ réel</p>
                        <p class="mt-1 text-sm font-semibold">{{ $deplacement->depart_reel_at?->format('d/m/Y H:i') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-ardoise-500">Arrivée réelle</p>
                        <p class="mt-1 text-sm font-semibold">{{ $deplacement->arrivee_reelle_at?->format('d/m/Y H:i') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-ardoise-500">Distance</p>
                        <p class="mt-1 text-sm font-semibold">{{ $deplacement->distance_parcourue !== null ? $deplacement->distance_parcourue.' km' : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-ardoise-500">Coût total</p>
                        <p class="mt-1 text-sm font-semibold">@fcfa($deplacement->cout_total)</p>
                    </div>
                </div>
            </x-carte>

            {{-- Ouverture / clôture --}}
            @can('deplacements.gerer')
                @if($deplacement->statut === 'planifiee')
                    <x-carte titre="Ouvrir le déplacement" sous-titre="Le véhicule passera en déplacement et ne sera plus réservable">
                        <form method="POST" action="{{ route('deplacements.demarrer', $deplacement) }}" class="grid gap-5 sm:grid-cols-3">
                            @csrf
                            <x-champ nom="km_depart" libelle="Kilométrage au départ" type="number" min="0" obligatoire
                                     :valeur="$deplacement->vehicule?->kilometrage"/>
                            <x-champ nom="depart_reel_at" libelle="Heure de départ" type="datetime-local"
                                     :valeur="now()->format('Y-m-d\TH:i')"/>
                            <div class="flex items-end">
                                <button class="btn-primaire w-full">Démarrer le déplacement</button>
                            </div>
                        </form>
                    </x-carte>
                @elseif($deplacement->statut === 'en_cours')
                    <x-carte titre="Clôturer le déplacement" sous-titre="La clôture est bloquée tant que les données de déplacement sont incomplètes">
                        @if($manquants)
                            <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                                <p class="text-sm font-semibold text-amber-800">À compléter avant la clôture</p>
                                <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-amber-700">
                                    @foreach($manquants as $manquant)<li>{{ $manquant }}</li>@endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('deplacements.cloturer', $deplacement) }}" class="space-y-5">
                            @csrf
                            <div class="grid gap-5 sm:grid-cols-3">
                                <x-champ nom="km_arrivee" libelle="Kilométrage à l'arrivée" type="number"
                                         :min="$deplacement->km_depart" obligatoire :valeur="$deplacement->km_arrivee"/>
                                <x-champ nom="arrivee_reelle_at" libelle="Heure d'arrivée" type="datetime-local"
                                         :valeur="now()->format('Y-m-d\TH:i')"/>
                                <x-champ nom="carburant_consomme" libelle="Carburant consommé (litres)" type="number" step="0.01" min="0"
                                         :valeur="$deplacement->carburant_consomme"/>
                                <x-champ nom="cout_carburant" libelle="Coût carburant (FCFA)" type="number" step="1" min="0"
                                         :valeur="$deplacement->cout_carburant"/>
                                <x-champ nom="autres_frais" libelle="Autres frais (FCFA)" type="number" step="1" min="0"
                                         :valeur="$deplacement->autres_frais" aide="Péages, stationnement…"/>
                            </div>

                            <x-zone-texte nom="observations" libelle="Observations de déplacement" :lignes="3"
                                          :valeur="$deplacement->observations"/>

                            <button class="btn-primaire">Clôturer le déplacement</button>
                        </form>
                    </x-carte>

                    <x-carte titre="Signaler un incident">
                        <form method="POST" action="{{ route('deplacements.incident', $deplacement) }}" class="space-y-4">
                            @csrf
                            <x-zone-texte nom="observations" libelle="Description de l'incident" obligatoire :lignes="3"
                                          placeholder="Crevaison à hauteur de Bohicon, roue de secours montée, retard estimé à 1h30."/>
                            <button class="btn-danger">Enregistrer l'incident</button>
                        </form>
                    </x-carte>
                @endif
            @endcan

            @if($deplacement->observations)
                <x-carte titre="Journal de déplacement">
                    <p class="whitespace-pre-line text-sm text-ardoise-700">{{ $deplacement->observations }}</p>
                </x-carte>
            @endif
        </div>

        <div class="space-y-6">
            <x-carte titre="Ressources engagées">
                <dl class="space-y-4 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ardoise-500">Véhicule</dt>
                        <dd class="mt-1">
                            <a href="{{ route('vehicules.show', $deplacement->vehicule) }}" class="font-semibold text-petro-700 hover:underline">
                                {{ $deplacement->vehicule?->libelle }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ardoise-500">Chauffeur</dt>
                        <dd class="mt-1">
                            @if($deplacement->chauffeur)
                                <a href="{{ route('chauffeurs.show', $deplacement->chauffeur) }}" class="font-semibold text-petro-700 hover:underline">
                                    {{ $deplacement->chauffeur?->nom_complet }}
                                </a>
                                <p class="text-xs text-ardoise-500">{{ $deplacement->chauffeur?->telephone }}</p>
                            @else
                                <span class="text-ardoise-400">Sans chauffeur</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ardoise-500">Créneau prévu</dt>
                        <dd class="mt-1 font-medium">
                            {{ $deplacement->reservation?->date_debut->format('d/m/Y H:i') }}
                            → {{ $deplacement->reservation?->date_fin->format('d/m/Y H:i') }}
                        </dd>
                    </div>
                </dl>
            </x-carte>

            <x-carte titre="Détail des coûts">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-ardoise-600">Carburant</dt><dd class="font-medium">@fcfa($deplacement->cout_carburant)</dd></div>
                    <div class="flex justify-between"><dt class="text-ardoise-600">Autres frais</dt><dd class="font-medium">@fcfa($deplacement->autres_frais)</dd></div>
                    <div class="flex justify-between border-t border-ardoise-100 pt-2">
                        <dt class="font-semibold">Total</dt><dd class="font-bold text-petro-700">@fcfa($deplacement->cout_total)</dd>
                    </div>
                </dl>
            </x-carte>
        </div>
    </div>
@endsection
