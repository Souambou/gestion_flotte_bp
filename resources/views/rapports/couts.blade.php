@extends('layouts.app')
@section('titre', 'Coûts d\'exploitation')
@section('sous-titre', 'Carburant, frais de déplacement et interventions de maintenance')

@section('contenu')
    @include('rapports._entete', ['rapportExport' => 'couts'])

    @php
        $coutMaintenance = (float) $maintenances->sum('cout');
        $kmTotal = $vehicules->sum('kilometres');
        $coutKm = $kmTotal > 0 ? round(($coutDeplacements + $coutMaintenance) / $kmTotal) : 0;
    @endphp

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-statistique libelle="Coût des déplacements" :valeur="number_format($coutDeplacements, 0, ',', ' ')" unite="FCFA"/>
        <x-statistique libelle="Coût de maintenance" :valeur="number_format($coutMaintenance, 0, ',', ' ')" unite="FCFA" ton="ambre"/>
        <x-statistique libelle="Coût total"
                       :valeur="number_format($coutDeplacements + $coutMaintenance, 0, ',', ' ')" unite="FCFA" ton="vert"/>
        <x-statistique libelle="Coût au kilomètre" :valeur="number_format($coutKm, 0, ',', ' ')" unite="FCFA/km" ton="teal"/>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-carte titre="Coûts par véhicule" :padding="false">
            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead><tr><th>Véhicule</th><th>Déplacements</th><th>Km</th><th>Coût déplacements</th><th>Coût / km</th></tr></thead>
                    <tbody>
                    @foreach($vehicules->sortByDesc('cout') as $ligne)
                        <tr>
                            <td>
                                <a href="{{ route('vehicules.show', $ligne['vehicule']) }}" class="font-mono text-xs font-semibold text-petro-700 hover:underline">
                                    {{ $ligne['vehicule']->immatriculation }}
                                </a>
                                <p class="text-xs text-ardoise-500">{{ $ligne['vehicule']->marque }} {{ $ligne['vehicule']->modele }}</p>
                            </td>
                            <td>{{ $ligne['deplacements'] }}</td>
                            <td>{{ number_format($ligne['kilometres'], 0, ',', ' ') }}</td>
                            <td class="whitespace-nowrap font-medium">@fcfa($ligne['cout'])</td>
                            <td class="whitespace-nowrap text-ardoise-600">
                                {{ $ligne['kilometres'] > 0 ? number_format($ligne['cout'] / $ligne['kilometres'], 0, ',', ' ').' FCFA' : '—' }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-carte>

        <x-carte titre="Interventions de maintenance facturées" :padding="false">
            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead><tr><th>Intervention</th><th>Véhicule</th><th>Date</th><th>Coût</th></tr></thead>
                    <tbody>
                    @forelse($maintenances as $maintenance)
                        <tr>
                            <td>
                                <p class="font-medium">{{ $maintenance->intitule }}</p>
                                <p class="text-xs text-ardoise-500">{{ $maintenance->type_libelle }}</p>
                            </td>
                            <td class="font-mono text-xs">{{ $maintenance->vehicule?->immatriculation }}</td>
                            <td class="whitespace-nowrap text-ardoise-600">@dateFr($maintenance->date_realisee)</td>
                            <td class="whitespace-nowrap font-medium">@fcfa($maintenance->cout)</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-sm text-ardoise-500">Aucune intervention facturée sur la période.</td></tr>
                    @endforelse
                    </tbody>
                    @if($maintenances->isNotEmpty())
                        <tfoot>
                        <tr class="border-t-2 border-ardoise-200">
                            <td colspan="3" class="py-3 text-right text-sm font-semibold">Total maintenance</td>
                            <td class="py-3 font-bold text-petro-700">@fcfa($coutMaintenance)</td>
                        </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-carte>
    </div>
@endsection
