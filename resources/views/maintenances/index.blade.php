@extends('layouts.app')
@section('titre', 'Maintenance')
@section('sous-titre', 'Interventions planifiées et réalisées sur la flotte')

@section('contenu')

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-statistique libelle="Planifiées" :valeur="$compteurs['planifiee']" ton="ambre"/>
        <x-statistique libelle="En atelier" :valeur="$compteurs['en_cours']" ton="teal"/>
        <x-statistique libelle="En retard" :valeur="$compteurs['en_retard']" ton="rouge"
                       variation="Date prévue dépassée"/>
        <x-statistique libelle="Coût du mois" :valeur="number_format($compteurs['cout_mois'], 0, ',', ' ')" unite="FCFA"/>
    </div>

    <x-carte :padding="false">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-ardoise-100 p-4">
            <div>
                <label for="statut" class="mb-1 block text-xs font-medium text-ardoise-600">Statut</label>
                <select name="statut" id="statut" class="champ">
                    <option value="">Tous</option>
                    @foreach(\App\Models\Maintenance::STATUTS as $cle => $libelle)
                        <option value="{{ $cle }}" @selected(request('statut') === $cle)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="vehicule" class="mb-1 block text-xs font-medium text-ardoise-600">Véhicule</label>
                <select name="vehicule" id="vehicule" class="champ">
                    <option value="">Tous</option>
                    @foreach($vehicules as $vehicule)
                        <option value="{{ $vehicule->id }}" @selected(request('vehicule') == $vehicule->id)>{{ $vehicule->immatriculation }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn-secondaire">Filtrer</button>
            @can('maintenances.gerer')
                <a href="{{ route('maintenances.create') }}" class="btn-primaire ml-auto">Planifier une intervention</a>
            @endcan
        </form>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                <tr><th>Intervention</th><th>Véhicule</th><th>Type</th><th>Date prévue</th><th>Réalisée</th><th>Coût</th><th>Statut</th><th></th></tr>
                </thead>
                <tbody>
                @forelse($maintenances as $maintenance)
                    <tr>
                        <td>
                            <p class="font-medium">{{ $maintenance->intitule }}</p>
                            @if($maintenance->prestataire)
                                <p class="text-xs text-ardoise-500">{{ $maintenance->prestataire }}</p>
                            @endif
                        </td>
                        <td class="font-mono text-xs">{{ $maintenance->vehicule?->immatriculation }}</td>
                        <td class="text-ardoise-600">{{ $maintenance->type_libelle }}</td>
                        <td @class([
                            'whitespace-nowrap',
                            'font-semibold text-red-600' => $maintenance->statut === 'planifiee' && $maintenance->date_prevue?->isPast(),
                            'text-ardoise-600' => ! ($maintenance->statut === 'planifiee' && $maintenance->date_prevue?->isPast()),
                        ])>@dateFr($maintenance->date_prevue)</td>
                        <td class="whitespace-nowrap text-ardoise-600">@dateFr($maintenance->date_realisee)</td>
                        <td class="whitespace-nowrap">@fcfa($maintenance->cout)</td>
                        <td><x-badge :statut="$maintenance->statut" :libelle="$maintenance->statut_libelle"/></td>
                        <td class="text-right">
                            @can('maintenances.gerer')
                                <a href="{{ route('maintenances.edit', $maintenance) }}" class="text-sm font-medium text-petro-700 hover:underline">Modifier</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-vide titre="Aucune intervention" message="Planifiez les révisions pour éviter l'immobilisation des véhicules."/></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($maintenances->hasPages())
            <div class="border-t border-ardoise-100 p-4">{{ $maintenances->links() }}</div>
        @endif
    </x-carte>
@endsection
