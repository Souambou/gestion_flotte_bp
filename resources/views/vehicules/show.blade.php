@extends('layouts.app')
@section('titre', $vehicule->immatriculation)
@section('sous-titre', $vehicule->marque.' '.$vehicule->modele.' · '.$vehicule->type_libelle)

@section('contenu')
    <div class="grid gap-6 lg:grid-cols-3 lg:items-start">

        <div class="space-y-6 lg:col-span-2">

            <x-carte>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex gap-4">
                        @if($vehicule->photo_url)
                            <img src="{{ $vehicule->photo_url }}" alt="" class="h-24 w-32 rounded-lg object-cover">
                        @endif
                        <div>
                            <x-badge :statut="$vehicule->statut" :libelle="$vehicule->statut_libelle"/>
                            <p class="mt-2 font-display text-xl font-bold">{{ $vehicule->marque }} {{ $vehicule->modele }}</p>
                            <p class="text-sm text-ardoise-500">
                                {{ $vehicule->annee ?? '—' }} · {{ \App\Models\Vehicule::CARBURANTS[$vehicule->carburant] }}
                                · {{ $vehicule->nombre_places }} places
                                @if($vehicule->agence) · {{ $vehicule->agence?->nom }} @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 sans-impression">
                        @can('vehicules.modifier')
                            <a href="{{ route('vehicules.edit', $vehicule) }}" class="btn-secondaire">Modifier</a>
                        @endcan
                        @can('maintenances.gerer')
                            <a href="{{ route('maintenances.create', ['vehicule' => $vehicule->id]) }}" class="btn-secondaire">
                                Planifier une intervention
                            </a>
                        @endcan
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-4 border-t border-ardoise-100 pt-6 sm:grid-cols-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-ardoise-500">Kilométrage</p>
                        <p class="mt-1 font-display text-lg font-bold">{{ number_format($vehicule->kilometrage, 0, ',', ' ') }} km</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-ardoise-500">Occupation du mois</p>
                        <p class="mt-1 font-display text-lg font-bold text-petro-700">{{ $tauxOccupation }} %</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-ardoise-500">Assurance</p>
                        <p class="mt-1 text-sm font-semibold">@dateFr($vehicule->date_expiration_assurance)</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-ardoise-500">Visite technique</p>
                        <p class="mt-1 text-sm font-semibold">@dateFr($vehicule->date_visite_technique)</p>
                    </div>
                </div>
            </x-carte>

            @can('vehicules.modifier')
                <x-carte titre="Changer la disponibilité" sous-titre="Un véhicule en maintenance n'apparaît plus dans les affectations">
                    <form method="POST" action="{{ route('vehicules.statut', $vehicule) }}" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div class="min-w-[220px]">
                            <x-selecteur nom="statut" libelle="Nouvelle disponibilité"
                                         :options="\App\Models\Vehicule::STATUTS" :valeur="$vehicule->statut"/>
                        </div>
                        <button class="btn-primaire">Appliquer</button>
                    </form>
                </x-carte>
            @endcan

            <x-carte titre="Réservations récentes">
                <div class="overflow-x-auto">
                    <table class="tableau">
                        <thead>
                        <tr><th>Référence</th><th>Demandeur</th><th>Période</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                        @forelse($reservations as $reservation)
                            <tr class="cursor-pointer" onclick="window.location='{{ route('reservations.show', $reservation) }}'">
                                <td class="font-mono text-xs font-semibold text-petro-700">{{ $reservation->code }}</td>
                                <td>{{ $reservation->demandeur?->nom_complet }}</td>
                                <td class="whitespace-nowrap text-ardoise-600">{{ $reservation->date_debut->format('d/m/Y H:i') }}</td>
                                <td><x-badge :statut="$reservation->statut" :libelle="$reservation->statut_libelle"/></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-sm text-ardoise-500">Aucune réservation sur ce véhicule.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-carte>

            <x-carte titre="Derniers contrôles matinaux">
                @forelse($checklists as $checklist)
                    <a href="{{ route('checklists.show', $checklist) }}"
                       class="-mx-2 flex items-center justify-between gap-4 rounded-lg px-2 py-3 hover:bg-ardoise-50">
                        <div>
                            <p class="text-sm font-semibold">Contrôle du {{ $checklist->date_controle->format('d/m/Y') }}</p>
                            <p class="text-xs text-ardoise-500">
                                {{ $checklist->auteur?->nom_complet }} · {{ $checklist->taux_conformite }}% conforme
                                @if($checklist->nombre_anomalies) · {{ $checklist->nombre_anomalies }} anomalie(s) @endif
                            </p>
                        </div>
                        <x-badge :statut="$checklist->etat_general" :libelle="ucfirst($checklist->etat_general)"/>
                    </a>
                @empty
                    <p class="py-4 text-center text-sm text-ardoise-500">Aucun contrôle enregistré.</p>
                @endforelse
            </x-carte>
        </div>

        <div class="space-y-6">
            @if($alertes)
                <x-carte titre="Points de vigilance">
                    @foreach($alertes as $alerte)
                        <div class="flex gap-3 py-2">
                            <span @class([
                                'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                                'bg-red-500' => $alerte['niveau'] === 'danger',
                                'bg-amber-500' => $alerte['niveau'] !== 'danger',
                            ])></span>
                            <p class="text-sm text-ardoise-700">{{ $alerte['message'] }}</p>
                        </div>
                    @endforeach
                </x-carte>
            @endif

            <x-carte titre="Historique de maintenance">
                @forelse($vehicule->maintenances as $maintenance)
                    <div class="border-b border-ardoise-100 py-3 last:border-0">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">{{ $maintenance->intitule }}</p>
                                <p class="text-xs text-ardoise-500">
                                    {{ $maintenance->type_libelle }} ·
                                    @dateFr($maintenance->date_realisee ?? $maintenance->date_prevue)
                                </p>
                            </div>
                            <x-badge :statut="$maintenance->statut" :libelle="$maintenance->statut_libelle"/>
                        </div>
                        @if($maintenance->cout)
                            <p class="mt-1 text-xs font-medium text-ardoise-600">@fcfa($maintenance->cout)</p>
                        @endif
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-ardoise-500">Aucune intervention enregistrée.</p>
                @endforelse
            </x-carte>

            <x-carte titre="Déplacements récentes">
                @forelse($deplacements as $deplacement)
                    <a href="{{ route('deplacements.show', $deplacement) }}" class="-mx-2 block rounded-lg px-2 py-2.5 hover:bg-ardoise-50">
                        <p class="text-sm font-semibold">{{ $deplacement->code }}</p>
                        <p class="text-xs text-ardoise-500">
                            {{ $deplacement->chauffeur?->nom_complet ?? 'Sans chauffeur' }}
                            @if($deplacement->distance_parcourue) · {{ $deplacement->distance_parcourue }} km @endif
                        </p>
                    </a>
                @empty
                    <p class="py-4 text-center text-sm text-ardoise-500">Aucun déplacement enregistré.</p>
                @endforelse
            </x-carte>

            @if($vehicule->observations)
                <x-carte titre="Observations">
                    <p class="whitespace-pre-line text-sm text-ardoise-700">{{ $vehicule->observations }}</p>
                </x-carte>
            @endif

            @can('vehicules.supprimer')
                <form method="POST" action="{{ route('vehicules.destroy', $vehicule) }}"
                      data-confirmer="Le véhicule {{ $vehicule->immatriculation }} sera retiré de la flotte et ne pourra plus être réservé."
                      data-confirmer-titre="Retirer ce véhicule ?"
                      data-confirmer-bouton="Retirer" data-confirmer-danger>
                    @csrf
                    @method('DELETE')
                    <button class="btn-secondaire w-full !text-red-600">Retirer de la flotte</button>
                </form>
            @endcan
        </div>
    </div>
@endsection
