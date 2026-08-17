@extends('layouts.app')
@section('titre', 'Rapports')
@section('sous-titre', 'Indicateurs générés automatiquement à partir des données de la plateforme')

@section('contenu')
    @include('rapports._entete', ['rapportExport' => 'synthese'])

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-statistique libelle="Réservations" :valeur="$synthese['reservations_total']"
                       :variation="$synthese['reservations_terminees'].' mission(s) terminée(s)'"/>
        <x-statistique libelle="Taux de validation" :valeur="$synthese['taux_validation']" unite="%" ton="vert"
                       :variation="'Délai moyen : '.$synthese['delai_moyen_validation'].' h'"/>
        <x-statistique libelle="Occupation de la flotte" :valeur="$synthese['taux_occupation_flotte']" unite="%" ton="teal"
                       :variation="$synthese['vehicules_total'].' véhicule(s)'"/>
        <x-statistique libelle="Kilomètres parcourus"
                       :valeur="number_format($synthese['km_parcourus'], 0, ',', ' ')" unite="km"
                       :variation="'Coût : '.number_format($synthese['cout_total'], 0, ',', ' ').' FCFA'"/>
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-3">
        <x-carte titre="Évolution des réservations" class="lg:col-span-2">
            <div class="h-64"><canvas id="graphiqueEvolution"></canvas></div>
        </x-carte>

        <x-carte titre="Répartition par statut">
            <div class="h-64"><canvas id="graphiqueRepartition"></canvas></div>
        </x-carte>
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-4">
        <x-statistique libelle="Demandes en attente" :valeur="$synthese['reservations_en_attente']" ton="ambre"/>
        <x-statistique libelle="Déplacements en cours" :valeur="$synthese['deplacements_en_cours']" ton="teal"/>
        <x-statistique libelle="Litiges ouverts" :valeur="$synthese['litiges_ouverts']" ton="rouge"/>
        <x-statistique libelle="Anomalies checklists" :valeur="$synthese['anomalies_checklists']" ton="ambre"/>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-carte titre="Véhicules les plus sollicités" :padding="false">
            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead><tr><th>Véhicule</th><th>Déplacements</th><th>Km</th><th>Occupation</th></tr></thead>
                    <tbody>
                    @foreach($vehicules->take(8) as $ligne)
                        <tr>
                            <td>
                                <p class="font-mono text-xs font-semibold">{{ $ligne['vehicule']->immatriculation }}</p>
                                <p class="text-xs text-ardoise-500">{{ $ligne['vehicule']->marque }} {{ $ligne['vehicule']->modele }}</p>
                            </td>
                            <td>{{ $ligne['deplacements'] }}</td>
                            <td>{{ number_format($ligne['kilometres'], 0, ',', ' ') }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-16 overflow-hidden rounded-full bg-ardoise-100">
                                        <span class="block h-full rounded-full bg-petro-500" style="width: {{ min(100, $ligne['taux_occupation']) }}%"></span>
                                    </span>
                                    <span class="text-xs font-semibold">{{ $ligne['taux_occupation'] }}%</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-carte>

        <x-carte titre="Activité des chauffeurs" :padding="false">
            <x-slot:action>
                @can('rapports.exporter')
                    <a href="{{ route('rapports.export', ['rapport' => 'chauffeurs', 'format' => 'xlsx', 'debut' => $kpi->debut()->format('Y-m-d'), 'fin' => $kpi->fin()->format('Y-m-d')]) }}"
                       class="text-xs font-medium text-petro-700 hover:underline">Exporter</a>
                @endcan
            </x-slot:action>
            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead><tr><th>Chauffeur</th><th>Déplacements</th><th>Km</th><th>Heures</th></tr></thead>
                    <tbody>
                    @foreach($chauffeurs->take(8) as $ligne)
                        <tr>
                            <td>
                                <p class="font-medium">{{ $ligne['chauffeur']->nom_complet }}</p>
                                <p class="font-mono text-xs text-ardoise-500">{{ $ligne['chauffeur']->matricule }}</p>
                            </td>
                            <td>{{ $ligne['deplacements'] }}</td>
                            <td>{{ number_format($ligne['kilometres'], 0, ',', ' ') }}</td>
                            <td>{{ $ligne['heures'] }} h</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-carte>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const palette = window.paletteBeninPetro;

        new Chart(document.getElementById('graphiqueEvolution'), {
            type: 'line',
            data: {
                labels: @json($evolution->keys()->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('d/m'))),
                datasets: [{
                    label: 'Réservations',
                    data: @json($evolution->values()),
                    borderColor: palette.primaire,
                    backgroundColor: palette.clair + '33',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });

        new Chart(document.getElementById('graphiqueRepartition'), {
            type: 'doughnut',
            data: {
                labels: @json($repartition->keys()->map(fn ($c) => \App\Models\Reservation::STATUTS[$c] ?? $c)),
                datasets: [{
                    data: @json($repartition->values()),
                    backgroundColor: [palette.primaire, palette.accent, palette.clair, '#f59e0b', '#ef4444', '#94a3b8'],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '62%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
            },
        });
    });
</script>
@endpush
