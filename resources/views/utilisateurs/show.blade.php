@extends('layouts.app')
@section('titre', $utilisateur->nom_complet)
@section('sous-titre', $utilisateur->role_libelle.($utilisateur->departement_libelle ? ' · '.$utilisateur->departement_libelle : ''))

@section('contenu')
    <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
        <div class="space-y-6 lg:col-span-2">

            <x-carte>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-petro-100 font-display text-xl font-bold text-petro-700">
                            {{ $utilisateur->initiales }}
                        </span>
                        <div>
                            <x-badge :ton="$utilisateur->actif ? 'vert' : 'rouge'"
                                     :libelle="$utilisateur->actif ? 'Compte actif' : 'Compte désactivé'"/>
                            <p class="mt-2 font-display text-xl font-bold">{{ $utilisateur->nom_complet }}</p>
                            <p class="text-sm text-ardoise-500">
                                {{ $utilisateur->email }}
                                @if($utilisateur->telephone) · {{ $utilisateur->telephone }} @endif
                            </p>
                        </div>
                    </div>

                    @can('utilisateurs.gerer')
                        <a href="{{ route('utilisateurs.edit', $utilisateur) }}" class="btn-secondaire">Modifier</a>
                    @endcan
                </div>

                <dl class="mt-6 grid grid-cols-2 gap-4 border-t border-ardoise-100 pt-6 sm:grid-cols-4">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ardoise-500">Matricule</dt>
                        <dd class="mt-1 font-mono text-sm font-semibold">{{ $utilisateur->matricule ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ardoise-500">Département</dt>
                        <dd class="mt-1 text-sm font-semibold">{{ $utilisateur->departement_libelle ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ardoise-500">Créé le</dt>
                        <dd class="mt-1 text-sm font-semibold">{{ $utilisateur->created_at->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ardoise-500">Dernière connexion</dt>
                        <dd class="mt-1 text-sm font-semibold">{{ $utilisateur->derniere_connexion_at?->format('d/m/Y H:i') ?? 'Jamais' }}</dd>
                    </div>
                </dl>
            </x-carte>

            <x-carte titre="Dernières réservations" :padding="false">
                <div class="overflow-x-auto">
                    <table class="tableau">
                        <thead><tr><th>Code</th><th>Trajet</th><th>Départ</th><th>Véhicule</th><th>Statut</th></tr></thead>
                        <tbody>
                        @forelse($reservations as $reservation)
                            <tr class="cursor-pointer" onclick="window.location='{{ route('reservations.show', $reservation) }}'">
                                <td class="font-mono text-xs font-semibold text-petro-700">{{ $reservation->code }}</td>
                                <td class="text-ardoise-600">{{ $reservation->lieu_depart }} → {{ $reservation->lieu_arrivee }}</td>
                                <td class="whitespace-nowrap text-ardoise-600">{{ $reservation->date_debut->format('d/m/Y') }}</td>
                                <td class="font-mono text-xs">{{ $reservation->vehicule?->immatriculation ?? '—' }}</td>
                                <td><x-badge :statut="$reservation->statut" :libelle="$reservation->statut_libelle"/></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-sm text-ardoise-500">Aucune réservation.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-carte>

            <x-carte titre="Journal d'activité" sous-titre="Traçabilité des actions réalisées sur la plateforme">
                @forelse($activites as $activite)
                    <div class="flex gap-3 border-b border-ardoise-100 py-3 last:border-0">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-petro-400"></span>
                        <div>
                            <p class="text-sm text-ardoise-800">{{ $activite->description }}</p>
                            <p class="text-xs text-ardoise-500">
                                {{ $activite->created_at->format('d/m/Y à H:i') }}
                                @if($activite->adresse_ip) · {{ $activite->adresse_ip }} @endif
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-ardoise-500">Aucune activité enregistrée.</p>
                @endforelse
            </x-carte>
        </div>

        <div class="space-y-6">
            @can('utilisateurs.gerer')
                <x-carte titre="Administration du compte">
                    <div class="space-y-3">
                        <form method="POST" action="{{ route('utilisateurs.activation', $utilisateur) }}">
                            @csrf
                            <button class="btn-secondaire w-full">
                                {{ $utilisateur->actif ? 'Désactiver le compte' : 'Réactiver le compte' }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('utilisateurs.mot-de-passe', $utilisateur) }}"
                              data-confirmer="Un nouveau mot de passe provisoire sera généré et communiqué à {{ $utilisateur->nom_complet }}."
                              data-confirmer-titre="Réinitialiser le mot de passe ?"
                              data-confirmer-bouton="Réinitialiser">
                            @csrf
                            <button class="btn-secondaire w-full">Réinitialiser le mot de passe</button>
                        </form>
                    </div>

                    <p class="mt-4 text-xs leading-relaxed text-ardoise-500">
                        Un compte désactivé est déconnecté et ne peut plus s'authentifier ; ses réservations passées
                        restent conservées pour l'historique et les rapports.
                    </p>
                </x-carte>

                @if($utilisateur->id !== auth()->id())
                    <form method="POST" action="{{ route('utilisateurs.destroy', $utilisateur) }}"
                          data-confirmer="Le compte de {{ $utilisateur->nom_complet }} sera supprimé. Ses réservations passées restent conservées."
                          data-confirmer-titre="Supprimer ce compte ?"
                          data-confirmer-bouton="Supprimer" data-confirmer-danger>
                        @csrf
                        @method('DELETE')
                        <button class="btn-secondaire w-full !text-red-600">Supprimer le compte</button>
                    </form>
                @endif
            @endcan
        </div>
    </div>
@endsection
