@extends('layouts.app')
@section('titre', $chauffeur->nom_complet)
@section('sous-titre', 'Matricule '.$chauffeur->matricule)

@section('contenu')
    <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
        <div class="space-y-6 lg:col-span-2">

            <x-carte>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        @if($chauffeur->photo_url)
                            <img src="{{ $chauffeur->photo_url }}" alt="" class="h-20 w-20 rounded-full object-cover">
                        @else
                            <span class="flex h-20 w-20 items-center justify-center rounded-full bg-petro-100 font-display text-2xl font-bold text-petro-700">
                                {{ mb_substr($chauffeur->prenom, 0, 1) }}{{ mb_substr($chauffeur->nom, 0, 1) }}
                            </span>
                        @endif
                        <div>
                            <x-badge :statut="$chauffeur->statut" :libelle="$chauffeur->statut_libelle"/>
                            <p class="mt-2 font-display text-xl font-bold">{{ $chauffeur->nom_complet }}</p>
                            <p class="text-sm text-ardoise-500">
                                {{ $chauffeur->telephone }}

                            </p>
                        </div>
                    </div>

                    @can('chauffeurs.modifier')
                        <a href="{{ route('chauffeurs.edit', $chauffeur) }}" class="btn-secondaire sans-impression">Modifier</a>
                    @endcan
                </div>

                <div class="mt-6 grid grid-cols-3 gap-4 border-t border-ardoise-100 pt-6">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-ardoise-500">Déplacements ce mois</p>
                        <p class="mt-1 font-display text-lg font-bold">{{ $statistiques['deplacements_mois'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-ardoise-500">Kilomètres ce mois</p>
                        <p class="mt-1 font-display text-lg font-bold">{{ number_format($statistiques['km_mois'], 0, ',', ' ') }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-ardoise-500">Déplacements au total</p>
                        <p class="mt-1 font-display text-lg font-bold">{{ $statistiques['deplacements_total'] }}</p>
                    </div>
                </div>
            </x-carte>

            <x-carte titre="Déplacements récentes">
                <div class="overflow-x-auto">
                    <table class="tableau">
                        <thead><tr><th>Déplacement</th><th>Véhicule</th><th>Départ</th><th>Distance</th><th>Statut</th></tr></thead>
                        <tbody>
                        @forelse($deplacements as $deplacement)
                            <tr class="cursor-pointer" onclick="window.location='{{ route('deplacements.show', $deplacement) }}'">
                                <td class="font-mono text-xs font-semibold text-petro-700">{{ $deplacement->code }}</td>
                                <td>{{ $deplacement->vehicule?->immatriculation }}</td>
                                <td class="whitespace-nowrap text-ardoise-600">{{ $deplacement->depart_reel_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>{{ $deplacement->distance_parcourue ? $deplacement->distance_parcourue.' km' : '—' }}</td>
                                <td><x-badge :statut="$deplacement->statut" :libelle="$deplacement->statut_libelle"/></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-sm text-ardoise-500">Aucun déplacement enregistré.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-carte>
        </div>

        <div class="space-y-6">
            <x-carte titre="Permis de conduire">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ardoise-500">Numéro</dt>
                        <dd class="font-mono font-medium">{{ $chauffeur->numero_permis }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ardoise-500">Catégorie</dt>
                        <dd class="font-medium">{{ $chauffeur->categorie_permis }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ardoise-500">Expiration</dt>
                        <dd @class(['font-medium', 'text-red-600' => $chauffeur->permis_expire])>
                            @dateFr($chauffeur->date_expiration_permis)
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ardoise-500">Embauche</dt>
                        <dd class="font-medium">@dateFr($chauffeur->date_embauche)</dd>
                    </div>
                </dl>

                @if($chauffeur->permis_expire)
                    <p class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-xs font-medium text-red-700">
                        Permis expiré : ce chauffeur ne doit plus être affecté à un déplacement.
                    </p>
                @endif
            </x-carte>

            <x-carte titre="Réservations à venir">
                @forelse($reservations->whereIn('statut', ['validee', 'en_cours']) as $reservation)
                    <a href="{{ route('reservations.show', $reservation) }}" class="-mx-2 block rounded-lg px-2 py-2.5 hover:bg-ardoise-50">
                        <p class="text-sm font-semibold">{{ $reservation->code }}</p>
                        <x-trajet :depart="$reservation->lieu_depart" :arrivee="$reservation->lieu_arrivee" compact/>
                        <p class="mt-0.5 text-xs text-ardoise-500">{{ $reservation->date_debut->format('d/m/Y H:i') }}</p>
                    </a>
                @empty
                    <p class="py-4 text-center text-sm text-ardoise-500">Aucune affectation à venir.</p>
                @endforelse
            </x-carte>

            @if($chauffeur->observations)
                <x-carte titre="Observations">
                    <p class="whitespace-pre-line text-sm text-ardoise-700">{{ $chauffeur->observations }}</p>
                </x-carte>
            @endif

            @can('chauffeurs.supprimer')
                <form method="POST" action="{{ route('chauffeurs.destroy', $chauffeur) }}"
                      data-confirmer="{{ $chauffeur->nom_complet }} ne pourra plus être affecté à un déplacement."
                      data-confirmer-titre="Retirer ce chauffeur ?"
                      data-confirmer-bouton="Retirer" data-confirmer-danger>
                    @csrf
                    @method('DELETE')
                    <button class="btn-secondaire w-full !text-red-600">Retirer ce chauffeur</button>
                </form>
            @endcan
        </div>
    </div>
@endsection
