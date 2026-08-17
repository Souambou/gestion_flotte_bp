@extends('layouts.app')
@section('titre', 'Déplacements')
@section('sous-titre', 'Suivi opérationnel des trajets : ouverture, kilométrage, clôture')

@section('contenu')

    <div class="mb-5 flex flex-wrap gap-2">
        <a href="{{ route('deplacements.index') }}"
           @class(['btn-secondaire', '!bg-petro-700 !text-white !border-petro-700' => ! request('statut')])>Toutes</a>
        @foreach(\App\Models\Deplacement::STATUTS as $cle => $libelle)
            <a href="{{ route('deplacements.index', ['statut' => $cle]) }}"
               @class(['btn-secondaire', '!bg-petro-700 !text-white !border-petro-700' => request('statut') === $cle])>
                {{ $libelle }} <span class="rounded-full bg-black/10 px-1.5 text-xs">{{ $compteurs[$cle] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <x-carte :padding="false">
        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                <tr>
                    <th>Déplacement</th><th>Véhicule</th><th>Chauffeur</th><th>Départ réel</th>
                    <th>Distance</th><th>Département</th><th>Statut</th><th class="text-right">Détail</th>
                </tr>
                </thead>
                <tbody>
                @forelse($deplacements as $deplacement)
                    <tr>
                        <td>
                            <p class="font-mono text-xs font-semibold text-petro-700">{{ $deplacement->code }}</p>
                            <p class="text-xs text-ardoise-500">{{ $deplacement->reservation?->demandeur->nom_complet }}</p>
                        </td>
                        <td>{{ $deplacement->vehicule?->immatriculation }}</td>
                        <td class="text-ardoise-600">{{ $deplacement->chauffeur?->nom_complet ?? 'Sans chauffeur' }}</td>
                        <td class="whitespace-nowrap text-ardoise-600">{{ $deplacement->depart_reel_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $deplacement->distance_parcourue ? $deplacement->distance_parcourue.' km' : '—' }}</td>
                        <td class="text-xs text-ardoise-600">{{ $deplacement->reservation?->departement_libelle }}</td>
                        <td><x-badge :statut="$deplacement->statut" :libelle="$deplacement->statut_libelle"/></td>
                        <td class="text-right">
                            <a href="{{ route('deplacements.show', $deplacement) }}"
                               class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-ardoise-500 transition hover:bg-petro-50 hover:text-petro-700"
                               title="Voir le détail du déplacement {{ $deplacement->code }}"
                               aria-label="Voir le détail du déplacement {{ $deplacement->code }}">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M2.25 12S5.75 5.25 12 5.25 21.75 12 21.75 12 18.25 18.75 12 18.75 2.25 12 2.25 12z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-vide titre="Aucun déplacement" message="Les déplacements sont créés automatiquement à la validation d'une réservation."/></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($deplacements->hasPages())
            <div class="border-t border-ardoise-100 p-4">{{ $deplacements->links() }}</div>
        @endif
    </x-carte>
@endsection
