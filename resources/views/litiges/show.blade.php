@extends('layouts.app')
@section('titre', 'Litige '.$litige->reference)
@section('sous-titre', $litige->objet)

@section('contenu')
    <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
        <div class="space-y-6 lg:col-span-2">

            <x-carte>
                <div class="flex flex-wrap items-center gap-3">
                    <x-badge :statut="$litige->statut" :libelle="$litige->statut_libelle"/>
                    <x-badge :statut="$litige->gravite" :libelle="'Gravité '.$litige->gravite"/>
                    <span class="text-sm text-ardoise-500">{{ $litige->type_libelle }}</span>
                </div>

                <h2 class="mt-4 font-display text-xl font-bold">{{ $litige->objet }}</h2>
                <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-ardoise-700">{{ $litige->description }}</p>

                <p class="mt-5 border-t border-ardoise-100 pt-4 text-xs text-ardoise-500">
                    Déclaré par {{ $litige->declarant?->nom_complet }} le {{ $litige->created_at->format('d/m/Y à H:i') }}
                </p>
            </x-carte>

            @if($litige->resolution)
                <x-carte titre="Résolution">
                    <p class="whitespace-pre-line text-sm leading-relaxed text-ardoise-700">{{ $litige->resolution }}</p>
                    <p class="mt-4 text-xs text-ardoise-500">
                        {{ $litige->responsable?->nom_complet }}
                        @if($litige->resolu_at) · {{ $litige->resolu_at->format('d/m/Y à H:i') }} @endif
                    </p>
                </x-carte>
            @endif

            @can('litiges.traiter')
                <x-carte titre="Traiter le dossier" sous-titre="Le déclarant est notifié à chaque mise à jour">
                    <form method="POST" action="{{ route('litiges.update', $litige) }}" class="space-y-5">
                        @csrf
                        @method('PUT')
                        <x-selecteur nom="statut" libelle="Statut du dossier" obligatoire
                                     :options="\App\Models\Litige::STATUTS" :valeur="$litige->statut"/>
                        <x-zone-texte nom="resolution" libelle="Résolution apportée" :lignes="5" :valeur="$litige->resolution"
                                      placeholder="Véhicule de remplacement fourni le lendemain, geste commercial accordé."
                                      aide="Obligatoire pour passer le dossier en « Résolu » ou « Clos »."/>
                        <button class="btn-primaire">Mettre à jour le dossier</button>
                    </form>
                </x-carte>
            @endcan
        </div>

        <div class="space-y-6">
            <x-carte titre="Éléments liés">
                <dl class="space-y-4 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ardoise-500">Réservation</dt>
                        <dd class="mt-1">
                            @if($litige->reservation)
                                <a href="{{ route('reservations.show', $litige->reservation) }}" class="font-semibold text-petro-700 hover:underline">
                                    {{ $litige->reservation?->code }}
                                </a>
                                <p class="text-xs text-ardoise-500">
                                    {{ $litige->reservation?->lieu_depart }} → {{ $litige->reservation?->lieu_arrivee }}
                                </p>
                            @else
                                <span class="text-ardoise-400">Aucune</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ardoise-500">Véhicule</dt>
                        <dd class="mt-1">
                            @if($litige->vehicule)
                                <a href="{{ route('vehicules.show', $litige->vehicule) }}" class="font-semibold text-petro-700 hover:underline">
                                    {{ $litige->vehicule?->libelle }}
                                </a>
                            @else
                                <span class="text-ardoise-400">Non concerné</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ardoise-500">Déclarant</dt>
                        <dd class="mt-1 font-medium">{{ $litige->declarant?->nom_complet }}</dd>
                        <dd class="text-xs text-ardoise-500">{{ $litige->declarant?->email }}</dd>
                    </div>
                    @if($litige->responsable)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-ardoise-500">Traité par</dt>
                            <dd class="mt-1 font-medium">{{ $litige->responsable?->nom_complet }}</dd>
                        </div>
                    @endif
                </dl>
            </x-carte>

            <a href="{{ route('litiges.index') }}" class="btn-secondaire w-full">Retour à la liste</a>
        </div>
    </div>
@endsection
