@extends('layouts.app')
@section('titre', 'Chauffeurs')
@section('sous-titre', $compteurs['total'].' chauffeur(s) enregistré(s)')

@section('contenu')

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-statistique libelle="Effectif" :valeur="$compteurs['total']"/>
        <x-statistique libelle="Disponibles" :valeur="$compteurs['disponible']" ton="vert"/>
        <x-statistique libelle="En déplacement" :valeur="$compteurs['en_deplacement']" ton="teal"/>
        <x-statistique libelle="Permis à renouveler" :valeur="$compteurs['permis_a_renouveler']" ton="ambre"
                       variation="Sous {{ config('beninpetro.maintenance.alerte_permis_jours') }} jours"/>
    </div>

    <x-carte :padding="false">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-ardoise-100 p-4">
            <div class="min-w-[200px] flex-1">
                <label for="q" class="mb-1 block text-xs font-medium text-ardoise-600">Rechercher</label>
                <input type="search" name="q" id="q" value="{{ request('q') }}" class="champ" placeholder="Nom, matricule ou téléphone">
            </div>
            <div>
                <label for="statut" class="mb-1 block text-xs font-medium text-ardoise-600">Statut</label>
                <select name="statut" id="statut" class="champ">
                    <option value="">Tous</option>
                    @foreach(\App\Models\Chauffeur::STATUTS as $cle => $libelle)
                        <option value="{{ $cle }}" @selected(request('statut') === $cle)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn-secondaire">Filtrer</button>
            @can('chauffeurs.creer')
                <a href="{{ route('chauffeurs.create') }}" class="btn-primaire ml-auto">Ajouter un chauffeur</a>
            @endcan
        </form>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                <tr>
                    <th>Chauffeur</th><th>Contact</th><th>Permis</th><th>Statut</th><th class="text-right">Détail</th>
                </tr>
                </thead>
                <tbody>
                @forelse($chauffeurs as $chauffeur)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                @if($chauffeur->photo_url)
                                    <img src="{{ $chauffeur->photo_url }}" alt="" class="h-9 w-9 rounded-full object-cover">
                                @else
                                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-petro-100 text-xs font-bold text-petro-700">
                                        {{ mb_substr($chauffeur->prenom, 0, 1) }}{{ mb_substr($chauffeur->nom, 0, 1) }}
                                    </span>
                                @endif
                                <div>
                                    <p class="font-medium">{{ $chauffeur->nom_complet }}</p>
                                    <p class="font-mono text-xs text-ardoise-500">{{ $chauffeur->matricule }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="text-ardoise-600">{{ $chauffeur->telephone }}</td>
                        <td>
                            <p class="text-ardoise-700">Catégorie {{ $chauffeur->categorie_permis }}</p>
                            @if($chauffeur->date_expiration_permis)
                                <p @class([
                                    'text-xs',
                                    'font-semibold text-red-600' => $chauffeur->permis_expire,
                                    'text-ardoise-500' => ! $chauffeur->permis_expire,
                                ])>
                                    {{ $chauffeur->permis_expire ? 'Expiré le ' : 'Valide jusqu\'au ' }}{{ $chauffeur->date_expiration_permis->format('d/m/Y') }}
                                </p>
                            @endif
                        </td>
                        
                        <td><x-badge :statut="$chauffeur->statut" :libelle="$chauffeur->statut_libelle"/></td>
                        <td class="text-right">
                            <a href="{{ route('chauffeurs.show', $chauffeur) }}"
                               class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-ardoise-500 transition hover:bg-petro-50 hover:text-petro-700"
                               title="Voir la fiche de {{ $chauffeur->nom_complet }}"
                               aria-label="Voir la fiche de {{ $chauffeur->nom_complet }}">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M2.25 12S5.75 5.25 12 5.25 21.75 12 21.75 12 18.25 18.75 12 18.75 2.25 12 2.25 12z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-vide titre="Aucun chauffeur" message="Ajoutez les conducteurs habilités à conduire les véhicules de la flotte."/></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($chauffeurs->hasPages())
            <div class="border-t border-ardoise-100 p-4">{{ $chauffeurs->links() }}</div>
        @endif
    </x-carte>
@endsection
