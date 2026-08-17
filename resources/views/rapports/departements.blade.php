@extends('layouts.app')
@section('titre', 'Demandes par service')
@section('sous-titre', 'Volume de demandes et taux de satisfaction, service par service')

@section('contenu')
    @include('rapports._entete', ['rapportExport' => 'departements'])

    {{-- ------------------------------------------------------------ Synthèse --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-statistique libelle="Demandes reçues" :valeur="$total['demandes']"/>
        <x-statistique libelle="Demandes satisfaites" :valeur="$total['satisfaites']" ton="vert"/>
        <x-statistique libelle="Non satisfaites" :valeur="$total['non_satisfaites']"
                       :ton="$total['non_satisfaites'] ? 'rouge' : 'ardoise'"/>
        <x-statistique libelle="Taux de satisfaction" :valeur="$total['taux_satisfaction']" unite="%" ton="teal"/>
    </div>

    {{-- ------------------------------------------------------------ Graphique --}}
    @if($total['demandes'])
        <x-carte titre="Répartition des demandes par service" class="mb-6">
            <div class="h-80"><canvas id="graphiqueDepartements"></canvas></div>
        </x-carte>
    @endif

    {{-- ------------------------------------------------------------ Tableau --}}
    <x-carte titre="Détail par service" :padding="false"
             sous-titre="Les sigles abrègent les noms de service : passez la souris pour lire l'intitulé complet.">
        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                <tr>
                    <th>Service</th>
                    <th class="text-right">Nombre de demandes</th>
                    <th class="text-right">Satisfaites</th>
                    <th class="text-right">Non satisfaites</th>
                    <th class="text-right">Taux de satisfaction</th>
                    <th class="text-right">Taux de non satisfaction</th>
                </tr>
                </thead>
                <tbody>
                @foreach($lignes as $ligne)
                    <tr @class(['opacity-60' => $ligne['demandes'] === 0])>
                        <td>
                            <span class="font-semibold text-ardoise-900" title="{{ $ligne['libelle'] }}">{{ $ligne['sigle'] }}</span>
                            <p class="text-[11px] text-ardoise-500">{{ $ligne['libelle'] }}</p>
                        </td>
                        <td class="text-right font-semibold">{{ $ligne['demandes'] }}</td>
                        <td class="text-right text-petro-700">{{ $ligne['satisfaites'] }}</td>
                        <td class="text-right @if($ligne['non_satisfaites']) text-red-600 @endif">{{ $ligne['non_satisfaites'] }}</td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <span class="h-2 w-16 overflow-hidden rounded-full bg-ardoise-100">
                                    <span class="block h-full rounded-full bg-petro-500"
                                          style="width: {{ min(100, $ligne['taux_satisfaction']) }}%"></span>
                                </span>
                                <span class="w-12 font-semibold">{{ $ligne['taux_satisfaction'] }} %</span>
                            </div>
                        </td>
                        <td class="text-right text-ardoise-600">{{ $ligne['taux_non_satisfaction'] }} %</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr class="border-t-2 border-ardoise-300 bg-ardoise-50 font-bold">
                    <td class="py-3">TOTAL</td>
                    <td class="py-3 text-right">{{ $total['demandes'] }}</td>
                    <td class="py-3 text-right text-petro-700">{{ $total['satisfaites'] }}</td>
                    <td class="py-3 text-right">{{ $total['non_satisfaites'] }}</td>
                    <td class="py-3 text-right text-petro-700">{{ $total['taux_satisfaction'] }} %</td>
                    <td class="py-3 text-right">{{ $total['taux_non_satisfaction'] }} %</td>
                </tr>
                </tfoot>
            </table>
        </div>
    </x-carte>

    <p class="mt-4 text-xs leading-relaxed text-ardoise-500">
        Une demande est comptée satisfaite dès lors qu'elle a été honorée : validée, en cours ou terminée.
        Les demandes refusées et les annulations constituent les demandes non satisfaites.
    </p>
@endsection

@push('scripts')
@if($total['demandes'])
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const p = window.paletteBeninPetro;
        const lignes = @json($lignes->where('demandes', '>', 0)->values());

        new Chart(document.getElementById('graphiqueDepartements'), {
            type: 'bar',
            data: {
                labels: lignes.map((l) => l.sigle),
                datasets: [
                    {
                        label: 'Satisfaites',
                        data: lignes.map((l) => l.satisfaites),
                        backgroundColor: p.primaire,
                        borderRadius: 4,
                    },
                    {
                        label: 'Non satisfaites',
                        data: lignes.map((l) => l.non_satisfaites),
                        backgroundColor: p.rouge,
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            // L'intitulé complet reste accessible au survol.
                            title: (items) => lignes[items[0].dataIndex].libelle,
                        },
                    },
                },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
                },
            },
        });
    });
</script>
@endif
@endpush
