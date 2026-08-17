@extends('layouts.app')
@section('titre', 'Taux d\'occupation de la flotte')
@section('sous-titre', 'Part du temps où chaque véhicule est engagé sur un déplacement')

@section('contenu')
    @include('rapports._entete', ['rapportExport' => 'occupation'])

    <div class="mb-6 grid gap-6 lg:grid-cols-3">
        <x-statistique libelle="Occupation moyenne de la flotte" :valeur="$tauxGlobal" unite="%" ton="vert"/>
        <x-statistique libelle="Véhicules analysés" :valeur="$vehicules->count()"/>
        <x-statistique libelle="Kilomètres cumulés"
                       :valeur="number_format($vehicules->sum('kilometres'), 0, ',', ' ')" unite="km"/>
    </div>

    <x-carte titre="Occupation par véhicule" class="mb-6">
        <div class="h-80"><canvas id="graphiqueOccupation"></canvas></div>
    </x-carte>

    <x-carte titre="Détail par véhicule" :padding="false">
        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                <tr><th>Véhicule</th><th>Type</th><th>Site</th><th>Réservations</th><th>Déplacements</th><th>Kilomètres</th><th>Occupation</th></tr>
                </thead>
                <tbody>
                @foreach($vehicules as $ligne)
                    <tr>
                        <td>
                            <a href="{{ route('vehicules.show', $ligne['vehicule']) }}" class="font-mono text-xs font-semibold text-petro-700 hover:underline">
                                {{ $ligne['vehicule']->immatriculation }}
                            </a>
                            <p class="text-xs text-ardoise-500">{{ $ligne['vehicule']->marque }} {{ $ligne['vehicule']->modele }}</p>
                        </td>
                        <td class="text-ardoise-600">{{ $ligne['vehicule']->type_libelle }}</td>
                        <td class="text-ardoise-600">{{ $ligne['vehicule']->agence?->nom ?? '—' }}</td>
                        <td>{{ $ligne['reservations'] }}</td>
                        <td>{{ $ligne['deplacements'] }}</td>
                        <td>{{ number_format($ligne['kilometres'], 0, ',', ' ') }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-24 overflow-hidden rounded-full bg-ardoise-100">
                                    <span @class([
                                        'block h-full rounded-full',
                                        'bg-red-400' => $ligne['taux_occupation'] < 20,
                                        'bg-amber-400' => $ligne['taux_occupation'] >= 20 && $ligne['taux_occupation'] < 50,
                                        'bg-petro-500' => $ligne['taux_occupation'] >= 50,
                                    ]) style="width: {{ min(100, $ligne['taux_occupation']) }}%"></span>
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

    <p class="mt-4 text-xs leading-relaxed text-ardoise-500">
        Un taux durablement inférieur à 20 % signale un véhicule sous-employé ; au-delà de 80 %, la flotte est saturée
        sur la période et les demandes risquent d'être refusées faute de disponibilité.
    </p>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        new Chart(document.getElementById('graphiqueOccupation'), {
            type: 'bar',
            data: {
                labels: @json($vehicules->pluck('vehicule.immatriculation')),
                datasets: [{
                    label: "Taux d'occupation (%)",
                    data: @json($vehicules->pluck('taux_occupation')),
                    backgroundColor: window.paletteBeninPetro.accent,
                    borderRadius: 4,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } },
            },
        });
    });
</script>
@endpush
