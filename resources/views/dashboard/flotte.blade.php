@extends('layouts.app')
@section('titre', 'Tableau de bord')
@section('sous-titre', 'Vue temps réel de la flotte — '.$kpi->debut()->format('d/m/Y').' au '.$kpi->fin()->format('d/m/Y'))

@section('contenu')

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <x-filtre-periode :action="route('dashboard')" :debut="$kpi->debut()" :fin="$kpi->fin()"/>
        @can('rapports.consulter')
            <a href="{{ route('rapports.index') }}" class="btn-secondaire">Ouvrir les rapports</a>
        @endcan
    </div>

    {{-- Indicateurs clés --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-statistique libelle="Demandes en attente" :valeur="$synthese['reservations_en_attente']"
                       ton="ambre" variation="À traiter par le responsable"
                       :href="route('reservations.index', ['statut' => 'en_attente'])"/>

        <x-statistique libelle="Taux d'occupation" :valeur="$synthese['taux_occupation_flotte']" unite="%"
                       ton="vert" variation="Moyenne de la flotte sur la période"/>

        <x-statistique libelle="Véhicules disponibles"
                       :valeur="$synthese['vehicules_disponibles'].' / '.$synthese['vehicules_total']"
                       variation="{{ $synthese['vehicules_en_maintenance'] }} en maintenance"
                       :href="route('vehicules.index', ['statut' => 'disponible'])"/>

        <x-statistique libelle="Déplacements en cours" :valeur="$synthese['deplacements_en_cours']" ton="teal"
                       variation="{{ $synthese['km_parcourus'] }} km parcourus sur la période"
                       :href="route('deplacements.index', ['statut' => 'en_cours'])"/>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">

        {{-- Colonne principale --}}
        <div class="space-y-6 lg:col-span-2">

            <x-carte titre="Activité des réservations" sous-titre="Volume quotidien sur la période sélectionnée">
                <canvas id="graphiqueEvolution" height="110"></canvas>
            </x-carte>

            <x-carte titre="Demandes à traiter" :sous-titre="$demandesEnAttente->count().' demande(s) en attente de validation'">
                <x-slot:action>
                    <a href="{{ route('reservations.index', ['statut' => 'en_attente']) }}"
                       class="text-sm font-medium text-petro-700 hover:underline">Tout voir</a>
                </x-slot:action>

                @forelse($demandesEnAttente as $demande)
                    <a href="{{ route('reservations.show', $demande) }}"
                       class="-mx-2 flex items-center gap-4 rounded-lg px-2 py-3 hover:bg-ardoise-50">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700">
                            {{ $demande->demandeur?->initiales }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-ardoise-800">
                                {{ $demande->code }} · {{ $demande->demandeur?->nom_complet }}
                            </p>
                            <x-trajet :depart="$demande->lieu_depart" :arrivee="$demande->lieu_arrivee" compact/>
                        </div>
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-medium text-ardoise-700">{{ $demande->date_debut->format('d/m à H:i') }}</p>
                            <p class="text-xs text-ardoise-500">{{ $demande->avec_chauffeur ? 'Avec chauffeur' : 'Sans chauffeur' }}</p>
                        </div>
                    </a>
                @empty
                    <x-vide titre="Aucune demande en attente"
                            message="Toutes les demandes de réservation ont été traitées."/>
                @endforelse
            </x-carte>

            <x-carte titre="Départs du jour" :sous-titre="today()->translatedFormat('l j F Y')">
                @forelse($departsDuJour as $depart)
                    <div class="-mx-2 flex items-center gap-4 rounded-lg px-2 py-3 hover:bg-ardoise-50">
                        <span class="w-14 shrink-0 font-mono text-sm font-bold text-petro-700">
                            {{ $depart->date_debut->format('H:i') }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">{{ $depart->vehicule?->libelle ?? 'Véhicule à affecter' }}</p>
                            <p class="truncate text-xs text-ardoise-500">
                                {{ $depart->demandeur?->nom_complet }}
                                @if($depart->chauffeur) · {{ $depart->chauffeur?->nom_complet }} @endif
                            </p>
                        </div>
                        <x-badge :statut="$depart->statut" :libelle="$depart->statut_libelle"/>
                        <a href="{{ route('reservations.show', $depart) }}" class="text-sm font-medium text-petro-700 hover:underline">Ouvrir</a>
                    </div>
                @empty
                    <x-vide titre="Aucun départ programmé aujourd'hui"
                            message="Les prochaines déplacements validées apparaîtront ici le jour du départ."/>
                @endforelse
            </x-carte>

            <x-carte titre="Véhicules les plus sollicités" sous-titre="Classement par taux d'occupation">
                <div class="overflow-x-auto">
                    <table class="tableau">
                        <thead>
                        <tr>
                            <th>Véhicule</th>
                            <th class="text-right">Réservations</th>
                            <th class="text-right">Kilomètres</th>
                            <th class="w-40">Occupation</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($topVehicules as $ligne)
                            <tr>
                                <td>
                                    <a href="{{ route('vehicules.show', $ligne['vehicule']) }}" class="font-medium text-ardoise-800 hover:text-petro-700">
                                        {{ $ligne['vehicule']->immatriculation }}
                                    </a>
                                    <p class="text-xs text-ardoise-500">{{ $ligne['vehicule']->marque }} {{ $ligne['vehicule']->modele }}</p>
                                </td>
                                <td class="text-right">{{ $ligne['reservations'] }}</td>
                                <td class="text-right">{{ number_format($ligne['kilometres'], 0, ',', ' ') }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-ardoise-100">
                                            <div class="h-full rounded-full bg-petro-500" style="width: {{ min(100, $ligne['taux_occupation']) }}%"></div>
                                        </div>
                                        <span class="w-10 text-right text-xs font-semibold">{{ $ligne['taux_occupation'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-carte>
        </div>

        {{-- Colonne latérale --}}
        <div class="space-y-6">

            <x-carte titre="Alertes de flotte" :sous-titre="$alertes->count().' point(s) de vigilance'">
                @forelse($alertes as $alerte)
                    <a href="{{ $alerte['lien'] }}" class="-mx-2 flex gap-3 rounded-lg px-2 py-2.5 hover:bg-ardoise-50">
                        <span @class([
                            'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                            'bg-red-500' => $alerte['niveau'] === 'danger',
                            'bg-amber-500' => $alerte['niveau'] !== 'danger',
                        ])></span>
                        <div class="min-w-0">
                            @if($alerte['vehicule'])
                                <p class="text-sm font-semibold text-ardoise-800">{{ $alerte['vehicule']->immatriculation }}</p>
                            @endif
                            <p class="text-xs text-ardoise-600">{{ $alerte['message'] }}</p>
                        </div>
                    </a>
                @empty
                    <p class="py-4 text-center text-sm text-ardoise-500">Aucune alerte : documents et échéances à jour.</p>
                @endforelse
            </x-carte>

            <x-carte titre="Répartition des demandes">
                <canvas id="graphiqueRepartition" height="180"></canvas>
                <dl class="mt-4 space-y-2 text-sm">
                    @foreach(\App\Models\Reservation::STATUTS as $cle => $libelle)
                        <div class="flex items-center justify-between">
                            <dt class="text-ardoise-600">{{ $libelle }}</dt>
                            <dd class="font-semibold">{{ $repartition[$cle] ?? 0 }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-carte>

            <x-carte titre="Déplacements en cours">
                @forelse($déplacementsEnCours as $deplacement)
                    <a href="{{ route('deplacements.show', $deplacement) }}" class="-mx-2 block rounded-lg px-2 py-2.5 hover:bg-ardoise-50">
                        <p class="text-sm font-semibold">{{ $deplacement->vehicule?->immatriculation }}</p>
                        <p class="text-xs text-ardoise-500">
                            {{ $deplacement->chauffeur?->nom_complet ?? 'Sans chauffeur' }} ·
                            départ {{ $deplacement->depart_reel_at?->format('d/m H:i') }}
                        </p>
                    </a>
                @empty
                    <p class="py-4 text-center text-sm text-ardoise-500">Aucun déplacement ouverte actuellement.</p>
                @endforelse
            </x-carte>

            <x-carte titre="Maintenance à venir">
                @forelse($maintenancesAVenir as $maintenance)
                    <div class="-mx-2 rounded-lg px-2 py-2.5">
                        <p class="text-sm font-semibold">{{ $maintenance->vehicule?->immatriculation }} — {{ $maintenance->intitule }}</p>
                        <p class="text-xs text-ardoise-500">
                            {{ $maintenance->type_libelle }} · prévue le @dateFr($maintenance->date_prevue)
                        </p>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-ardoise-500">Aucune intervention planifiée.</p>
                @endforelse
            </x-carte>

            @if($litigesOuverts->isNotEmpty())
                <x-carte titre="Litiges ouverts">
                    @foreach($litigesOuverts as $litige)
                        <a href="{{ route('litiges.show', $litige) }}" class="-mx-2 block rounded-lg px-2 py-2.5 hover:bg-ardoise-50">
                            <p class="text-sm font-semibold">{{ $litige->reference }} — {{ $litige->objet }}</p>
                            <p class="text-xs text-ardoise-500">{{ $litige->type_libelle }} · {{ $litige->created_at->diffForHumans() }}</p>
                        </a>
                    @endforeach
                </x-carte>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const p = window.paletteBeninPetro;

        new Chart(document.getElementById('graphiqueEvolution'), {
            type: 'line',
            data: {
                labels: @json($evolution->keys()->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('d/m'))),
                datasets: [{
                    label: 'Réservations',
                    data: @json($evolution->values()),
                    borderColor: p.primaire,
                    backgroundColor: 'rgba(1, 201, 109, .12)',
                    fill: true,
                    tension: .35,
                    pointRadius: 0,
                    borderWidth: 2,
                }],
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } },
            },
        });

        new Chart(document.getElementById('graphiqueRepartition'), {
            type: 'doughnut',
            data: {
                labels: @json(collect(\App\Models\Reservation::STATUTS)->values()),
                datasets: [{
                    data: @json(collect(array_keys(\App\Models\Reservation::STATUTS))->map(fn ($c) => $repartition[$c] ?? 0)),
                    backgroundColor: [p.ambre, p.accent, p.rouge, p.teal, p.ardoise, '#8B2E1E'],
                    borderWidth: 0,
                }],
            },
            options: { cutout: '65%', plugins: { legend: { display: false } } },
        });
    });
</script>
@endpush
