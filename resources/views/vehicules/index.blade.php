@extends('layouts.app')
@section('titre', 'Véhicules')
@section('sous-titre', $compteurs['total'].' véhicule(s) dans la flotte')

@section('contenu')

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-statistique libelle="Flotte totale" :valeur="$compteurs['total']"/>
        <x-statistique libelle="Disponibles" :valeur="$compteurs['disponible']" ton="vert"
                       :href="route('vehicules.index', ['statut' => 'disponible'])"/>
        <x-statistique libelle="En déplacement" :valeur="$compteurs['en_deplacement']" ton="teal"
                       :href="route('vehicules.index', ['statut' => 'en_deplacement'])"/>
        <x-statistique libelle="En maintenance" :valeur="$compteurs['en_maintenance']" ton="ambre"
                       :href="route('vehicules.index', ['statut' => 'en_maintenance'])"/>
    </div>

    <x-carte :padding="false">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-ardoise-100 p-4">
            <div class="min-w-[200px] flex-1">
                <label for="q" class="mb-1 block text-xs font-medium text-ardoise-600">Rechercher</label>
                <input type="search" name="q" id="q" value="{{ request('q') }}" class="champ" placeholder="Immatriculation, marque ou modèle">
            </div>
            <div>
                <label for="statut" class="mb-1 block text-xs font-medium text-ardoise-600">Disponibilité</label>
                <select name="statut" id="statut" class="champ">
                    <option value="">Toutes</option>
                    @foreach(\App\Models\Vehicule::STATUTS as $cle => $libelle)
                        <option value="{{ $cle }}" @selected(request('statut') === $cle)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="type" class="mb-1 block text-xs font-medium text-ardoise-600">Type</label>
                <select name="type" id="type" class="champ">
                    <option value="">Tous</option>
                    @foreach(\App\Models\Vehicule::TYPES as $cle => $libelle)
                        <option value="{{ $cle }}" @selected(request('type') === $cle)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="agence" class="mb-1 block text-xs font-medium text-ardoise-600">Site</label>
                <select name="agence" id="agence" class="champ">
                    <option value="">Tous</option>
                    @foreach($agences as $agence)
                        <option value="{{ $agence->id }}" @selected(request('agence') == $agence->id)>{{ $agence->nom }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn-secondaire">Filtrer</button>
            @can('vehicules.creer')
                <a href="{{ route('vehicules.create') }}" class="btn-primaire ml-auto">Ajouter un véhicule</a>
            @endcan
        </form>

        <div class="grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($vehicules as $vehicule)
                <a href="{{ route('vehicules.show', $vehicule) }}"
                   class="group rounded-xl2 border border-ardoise-200 p-4 transition hover:border-petro-400 hover:shadow-carte">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-sm font-bold text-ardoise-900">{{ $vehicule->immatriculation }}</p>
                            <p class="truncate text-sm text-ardoise-600">{{ $vehicule->marque }} {{ $vehicule->modele }}</p>
                        </div>
                        <x-badge :statut="$vehicule->statut" :libelle="$vehicule->statut_libelle"/>
                    </div>

                    @if($vehicule->photo_url)
                        <img src="{{ $vehicule->photo_url }}" alt="" class="mt-3 h-32 w-full rounded-lg object-cover">
                    @else
                        <div class="mt-3 flex h-32 items-center justify-center rounded-lg bg-ardoise-50">
                            <svg class="h-10 w-10 text-ardoise-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M5 17h14M6 17v2M18 17v2M4 13l1.6-5A2 2 0 017.5 6.6h9A2 2 0 0118.4 8l1.6 5v4H4v-4z"/>
                            </svg>
                        </div>
                    @endif

                    <dl class="mt-4 grid grid-cols-3 gap-2 text-xs">
                        <div>
                            <dt class="text-ardoise-500">Type</dt>
                            <dd class="font-semibold">{{ $vehicule->type_libelle }}</dd>
                        </div>
                        <div>
                            <dt class="text-ardoise-500">Places</dt>
                            <dd class="font-semibold">{{ $vehicule->nombre_places }}</dd>
                        </div>
                        <div>
                            <dt class="text-ardoise-500">Kilométrage</dt>
                            <dd class="font-semibold">{{ number_format($vehicule->kilometrage, 0, ',', ' ') }} km</dd>
                        </div>
                    </dl>

                    @if($alertes = $vehicule->alertes())
                        <p class="mt-3 flex items-center gap-1.5 text-xs font-medium text-amber-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            {{ count($alertes) }} alerte(s) — {{ $alertes[0]['message'] }}
                        </p>
                    @endif
                </a>
            @empty
                <div class="sm:col-span-2 xl:col-span-3">
                    <x-vide titre="Aucun véhicule ne correspond"
                            message="Modifiez les filtres, ou ajoutez un premier véhicule à la flotte."/>
                </div>
            @endforelse
        </div>

        @if($vehicules->hasPages())
            <div class="border-t border-ardoise-100 p-4">{{ $vehicules->links() }}</div>
        @endif
    </x-carte>
@endsection
