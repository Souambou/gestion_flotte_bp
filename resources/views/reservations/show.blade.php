@extends('layouts.app')
@section('titre', 'Réservation '.$reservation->code)
@section('sous-titre', $reservation->demandeur?->nom_complet.' · demande du '.$reservation->created_at->format('d/m/Y à H:i'))

@section('contenu')
    {{-- Synthèse : pleine largeur, pour que les deux colonnes suivantes
         démarrent avec des contenus de hauteur comparable. --}}
    <x-carte class="mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <x-badge :statut="$reservation->statut" :libelle="$reservation->statut_libelle" class="text-sm"/>
                        <p class="mt-3 font-display text-xl font-bold text-ardoise-900">
                            <x-trajet :depart="$reservation->lieu_depart" :arrivee="$reservation->lieu_arrivee" class="!text-xl"/>
                        </p>
                        <p class="mt-1 text-sm text-ardoise-500">
                            Du {{ $reservation->date_debut->format('d/m/Y à H:i') }}
                            au {{ $reservation->date_fin->format('d/m/Y à H:i') }}
                            · {{ $reservation->duree_heures }} h
                            @if($reservation->distance_estimee_km) · ~{{ $reservation->distance_estimee_km }} km @endif
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2 sans-impression">
                        @if($reservation->peutEtreModifiee() && ($reservation->user_id === auth()->id() || auth()->user()->can('reservations.modifier')))
                            <a href="{{ route('reservations.edit', $reservation) }}" class="btn-secondaire">Modifier</a>
                        @endif

                        @if($reservation->peutEtreAnnulee() && ($reservation->user_id === auth()->id() || auth()->user()->can('reservations.annuler')))
                            <button type="button" class="btn-secondaire" onclick="document.getElementById('modale-annulation').showModal()">
                                Annuler la réservation
                            </button>
                        @endif

                        @if($reservation->deplacement)
                            <a href="{{ route('deplacements.show', $reservation->deplacement) }}" class="btn-primaire">Suivi de déplacement</a>
                        @endif
                    </div>
                </div>

                <ol class="mt-6 grid gap-4 border-t border-ardoise-100 pt-6 sm:grid-cols-4">
                    @php
                        $etapes = [
                            ['Soumise', $reservation->created_at, true],
                            ['Traitée', $reservation->traite_at, in_array($reservation->statut, ['validee','refusee','en_cours','terminee'])],
                            ['En cours', $reservation->deplacement?->depart_reel_at, in_array($reservation->statut, ['en_cours','terminee'])],
                            ['Terminée', $reservation->deplacement?->arrivee_reelle_at, $reservation->statut === 'terminee'],
                        ];
                    @endphp
                    @foreach($etapes as [$libelle, $date, $atteinte])
                        <li>
                            <div class="flex items-center gap-2">
                                <span @class(['h-2.5 w-2.5 rounded-full', 'bg-petro-500' => $atteinte, 'bg-ardoise-200' => ! $atteinte])></span>
                                <span @class(['text-sm font-semibold', 'text-ardoise-800' => $atteinte, 'text-ardoise-400' => ! $atteinte])>{{ $libelle }}</span>
                            </div>
                            <p class="ml-4.5 mt-1 pl-0.5 text-xs text-ardoise-500">
                                {{ $date ? $date->format('d/m/Y H:i') : '—' }}
                            </p>
                        </li>
                    @endforeach
                </ol>
    </x-carte>

    <div class="grid gap-6 lg:grid-cols-3 lg:items-start">

        <div class="space-y-6 lg:col-span-2">

            {{-- Décision du responsable --}}
            @if($reservation->statut === 'refusee')
                <x-carte titre="Demande non retenue">
                    <p class="text-sm text-ardoise-700"><strong>Motif :</strong> {{ $reservation->motif_refus }}</p>
                    @if($reservation->alternative_proposee)
                        <div class="mt-4 rounded-lg border border-petro-200 bg-petro-50 p-4">
                            <p class="text-sm font-semibold text-petro-800">Alternative proposée</p>
                            <p class="mt-1 text-sm text-petro-700">{{ $reservation->alternative_proposee }}</p>
                            @if($reservation->user_id === auth()->id())
                                <a href="{{ route('reservations.create') }}" class="btn-accent mt-4">Soumettre la demande révisée</a>
                            @endif
                        </div>
                    @endif
                </x-carte>
            @endif

            @if($reservation->statut === 'annulee')
                <x-carte titre="Réservation annulée">
                    <p class="text-sm text-ardoise-700">
                        Annulée le {{ $reservation->annule_at?->format('d/m/Y à H:i') }}
                        @if($reservation->motif_annulation) — {{ $reservation->motif_annulation }} @endif
                    </p>
                </x-carte>
            @endif

            {{-- Validation par le responsable --}}
            @can('reservations.valider')
                @if($reservation->peutEtreValidee())
                    <x-carte titre="Traiter la demande" sous-titre="Affectez un véhicule libre sur le créneau, ou motivez le refus">
                        <div x-data="{ onglet: 'valider' }">
                            <div class="mb-5 flex gap-2">
                                <button type="button" @click="onglet = 'valider'"
                                        :class="onglet === 'valider' ? 'btn-primaire' : 'btn-secondaire'">Valider</button>
                                <button type="button" @click="onglet = 'refuser'"
                                        :class="onglet === 'refuser' ? 'btn-danger' : 'btn-secondaire'">Refuser</button>
                            </div>

                            {{-- Validation --}}
                            <form x-show="onglet === 'valider'" method="POST" action="{{ route('reservations.valider', $reservation) }}" class="space-y-5">
                                @csrf

                                @if($alternatives['tous_vehicules']->isEmpty())
                                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                        Aucun véhicule n'est libre sur ce créneau. Proposez une alternative au demandeur via l'onglet Refuser.
                                    </div>
                                @else
                                    <div>
                                        <label for="vehicule_id" class="mb-1.5 block text-sm font-medium text-ardoise-700">
                                            Véhicule à affecter <span class="text-red-500">*</span>
                                        </label>
                                        <select name="vehicule_id" id="vehicule_id" required class="champ">
                                            <option value="">Sélectionner un véhicule disponible</option>
                                            @if($alternatives['vehicules']->isNotEmpty())
                                                <optgroup label="Correspond au type demandé">
                                                    @foreach($alternatives['vehicules'] as $vehicule)
                                                        <option value="{{ $vehicule->id }}">{{ $vehicule->libelle }} — {{ $vehicule->nombre_places }} places</option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                            <optgroup label="Autres véhicules libres sur le créneau">
                                                @foreach($alternatives['tous_vehicules']->whereNotIn('id', $alternatives['vehicules']->pluck('id')) as $vehicule)
                                                    <option value="{{ $vehicule->id }}">{{ $vehicule->libelle }} — {{ $vehicule->type_libelle }}</option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                        @error('vehicule_id')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                                    </div>

                                    @if($reservation->avec_chauffeur)
                                        <div>
                                            <label for="chauffeur_id" class="mb-1.5 block text-sm font-medium text-ardoise-700">
                                                Chauffeur <span class="text-red-500">*</span>
                                            </label>
                                            <select name="chauffeur_id" id="chauffeur_id" required class="champ">
                                                <option value="">Sélectionner un chauffeur disponible</option>
                                                @foreach($alternatives['chauffeurs'] as $chauffeur)
                                                    <option value="{{ $chauffeur->id }}">
                                                        {{ $chauffeur->nom_complet }} — permis {{ $chauffeur->categorie_permis }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('chauffeur_id')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                                            @if($alternatives['chauffeurs']->isEmpty())
                                                <p class="mt-1 text-xs text-amber-700">Aucun chauffeur libre sur ce créneau.</p>
                                            @endif
                                        </div>
                                    @else
                                        <p class="rounded-lg bg-ardoise-50 px-4 py-3 text-sm text-ardoise-600">
                                            Demande sans chauffeur : le commercial conduit lui-même.
                                        </p>
                                    @endif

                                    <button class="btn-primaire w-full">Valider et notifier le demandeur</button>
                                @endif
                            </form>

                            {{-- Refus --}}
                            <form x-show="onglet === 'refuser'" x-cloak method="POST"
                                  action="{{ route('reservations.refuser', $reservation) }}" class="space-y-5">
                                @csrf
                                <x-zone-texte nom="motif_refus" libelle="Motif du refus" obligatoire :lignes="3"
                                              placeholder="Aucun pick-up disponible sur ce créneau : les trois véhicules sont engagés sur la tournée Nord."/>
                                <x-zone-texte nom="alternative_proposee" libelle="Alternative proposée" :lignes="3"
                                              placeholder="Créneau du 12/08 à partir de 8h avec un Hilux, ou berline disponible dès 14h le même jour."
                                              aide="L'alternative est envoyée au demandeur avec la notification de refus."/>
                                <button class="btn-danger w-full">Refuser et proposer une alternative</button>
                            </form>
                        </div>
                    </x-carte>
                @endif
            @endcan


            {{-- Évaluation --}}
            @if($reservation->statut === 'terminee' && $reservation->user_id === auth()->id())
                <x-carte titre="Votre évaluation" sous-titre="Votre retour aide à améliorer la qualité du service">
                    @if($reservation->avis)
                        <p class="text-sm text-ardoise-700">
                            Note globale : <strong>{{ $reservation->avis->note }}/5</strong>
                        </p>
                        @if($reservation->avis->commentaire)
                            <p class="mt-2 text-sm text-ardoise-600">« {{ $reservation->avis->commentaire }} »</p>
                        @endif
                    @else
                        <form method="POST" action="{{ route('reservations.avis', $reservation) }}" class="space-y-4">
                            @csrf
                            <div class="grid gap-4 sm:grid-cols-3">
                                <x-selecteur nom="note" libelle="Note globale" obligatoire
                                             :options="[5 => '5 — Excellent', 4 => '4 — Bien', 3 => '3 — Correct', 2 => '2 — Insuffisant', 1 => '1 — Mauvais']"
                                             vide="Choisir"/>
                                <x-selecteur nom="note_vehicule" libelle="Véhicule"
                                             :options="[5 => '5', 4 => '4', 3 => '3', 2 => '2', 1 => '1']" vide="—"/>
                                @if($reservation->chauffeur)
                                    <x-selecteur nom="note_chauffeur" libelle="Chauffeur"
                                                 :options="[5 => '5', 4 => '4', 3 => '3', 2 => '2', 1 => '1']" vide="—"/>
                                @endif
                            </div>
                            <x-zone-texte nom="commentaire" libelle="Commentaire" :lignes="3"
                                          placeholder="Véhicule propre, chauffeur ponctuel."/>
                            <button class="btn-primaire">Envoyer mon évaluation</button>
                        </form>
                    @endif
                </x-carte>
            @endif
        </div>

        {{-- Colonne latérale --}}
        <div class="space-y-6">

            <x-carte titre="Affectation">
                <dl class="space-y-4 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ardoise-500">Véhicule</dt>
                        <dd class="mt-1">
                            @if($reservation->vehicule)
                                <a href="{{ route('vehicules.show', $reservation->vehicule) }}" class="font-semibold text-petro-700 hover:underline">
                                    {{ $reservation->vehicule?->libelle }}
                                </a>
                                <p class="text-xs text-ardoise-500">
                                    {{ $reservation->vehicule?->type_libelle }} · {{ $reservation->vehicule?->nombre_places }} places
                                </p>
                            @else
                                <span class="text-ardoise-400">En attente d'affectation</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ardoise-500">Chauffeur</dt>
                        <dd class="mt-1">
                            @if($reservation->chauffeur)
                                <a href="{{ route('chauffeurs.show', $reservation->chauffeur) }}" class="font-semibold text-petro-700 hover:underline">
                                    {{ $reservation->chauffeur?->nom_complet }}
                                </a>
                                <p class="text-xs text-ardoise-500">{{ $reservation->chauffeur?->telephone }}</p>
                            @else
                                <span class="text-ardoise-400">{{ $reservation->avec_chauffeur ? 'À affecter' : 'Sans chauffeur' }}</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ardoise-500">Demandeur</dt>
                        <dd class="mt-1 font-medium">{{ $reservation->demandeur?->nom_complet }}</dd>
                        <dd class="text-xs text-ardoise-500">{{ $reservation->demandeur?->email }}</dd>
                    </div>

                    @if($reservation->validateur)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-ardoise-500">Traitée par</dt>
                            <dd class="mt-1 font-medium">{{ $reservation->validateur?->nom_complet }}</dd>
                            <dd class="text-xs text-ardoise-500">{{ $reservation->traite_at?->format('d/m/Y à H:i') }}</dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ardoise-500">Département</dt>
                        <dd class="mt-1 font-medium">{{ $reservation->departement_libelle }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ardoise-500">Type de déplacement</dt>
                        <dd class="mt-1 font-medium">{{ $reservation->type_deplacement_libelle }}</dd>
                    </div>
                </dl>
            </x-carte>

            <x-carte titre="Motif du déplacement">
                <p class="text-sm leading-relaxed text-ardoise-700">{{ $reservation->motif }}</p>
            </x-carte>

            @if($cleMaps)
                <x-carte titre="Trajet" :padding="false">
                    <iframe class="h-64 w-full rounded-b-xl2" loading="lazy" style="border:0" allowfullscreen
                            src="https://www.google.com/maps/embed/v1/directions?key={{ $cleMaps }}&origin={{ urlencode($reservation->lieu_depart) }}&destination={{ urlencode($reservation->lieu_arrivee) }}&region=bj"></iframe>
                </x-carte>
            @endif

            <x-carte titre="Litiges liés">
                @forelse($reservation->litiges as $litige)
                    <a href="{{ route('litiges.show', $litige) }}" class="-mx-2 block rounded-lg px-2 py-2 hover:bg-ardoise-50">
                        <p class="text-sm font-semibold">{{ $litige->reference }} — {{ $litige->objet }}</p>
                        <x-badge :statut="$litige->statut" :libelle="$litige->statut_libelle" class="mt-1"/>
                    </a>
                @empty
                    <p class="text-sm text-ardoise-500">Aucun litige déclaré sur cette réservation.</p>
                @endforelse
                <a href="{{ route('litiges.create', ['reservation' => $reservation->id]) }}" class="btn-secondaire mt-4 w-full">
                    Déclarer un litige
                </a>
            </x-carte>
        </div>
    </div>

    {{-- Modale d'annulation --}}
    <dialog id="modale-annulation" class="rounded-xl2 p-0 backdrop:bg-ardoise-900/40">
        <form method="POST" action="{{ route('reservations.annuler', $reservation) }}" class="w-[min(28rem,90vw)] p-6">
            @csrf
            <h3 class="font-display text-lg font-bold">Annuler la réservation {{ $reservation->code }}</h3>
            <p class="mt-2 text-sm text-ardoise-600">
                @if($reservation->estAnnulableSansPenalite())
                    L'annulation intervient plus de {{ config('beninpetro.reservation.delai_annulation_heures') }} h avant le départ.
                @else
                    Le départ est imminent : le responsable de flotte sera notifié immédiatement.
                @endif
            </p>
            <div class="mt-4">
                <x-zone-texte nom="motif_annulation" libelle="Motif (facultatif)" :lignes="3"
                              placeholder="Réunion client reportée."/>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" class="btn-secondaire" onclick="document.getElementById('modale-annulation').close()">Revenir</button>
                <button type="submit" class="btn-danger">Confirmer l'annulation</button>
            </div>
        </form>
    </dialog>
@endsection
