@extends('layouts.app')
@section('titre', 'Déclarer un litige')
@section('sous-titre', 'Le responsable de flotte est notifié dès l\'enregistrement')

@section('contenu')
    <form method="POST" action="{{ route('litiges.store') }}">
        @csrf

        <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
        <div class="space-y-6 lg:col-span-2">
            <x-carte titre="Objet du litige">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-selecteur nom="type" libelle="Nature du litige" obligatoire
                                 :options="\App\Models\Litige::TYPES" vide="Sélectionner"/>
                    <x-selecteur nom="gravite" libelle="Gravité" obligatoire
                                 :options="['faible' => 'Faible', 'moyenne' => 'Moyenne', 'elevee' => 'Élevée']"
                                 valeur="moyenne"/>
                    <div class="sm:col-span-2">
                        <x-champ nom="objet" libelle="Objet" obligatoire
                                 placeholder="Retard de 3 heures sur le déplacement Cotonou — Parakou"/>
                    </div>
                </div>

                <div class="mt-5">
                    <x-zone-texte nom="description" libelle="Description détaillée" obligatoire :lignes="6"
                                  placeholder="Décrivez les faits, l'heure, les personnes concernées et les conséquences sur votre activité."
                                  aide="15 caractères minimum. Plus la description est précise, plus le traitement est rapide."/>
                </div>
            </x-carte>
        </div>

        <div class="space-y-6">
            <x-carte titre="Réservation concernée">
                <x-selecteur nom="reservation_id" libelle="Réservation" vide="Aucune réservation liée"
                             :options="$reservations->mapWithKeys(fn ($r) => [$r->id => $r->code.' — '.$r->lieu_depart.' → '.$r->lieu_arrivee])"
                             :valeur="$reservationSelectionnee"/>
                <p class="mt-3 text-xs leading-relaxed text-ardoise-500">
                    Lier une réservation rattache automatiquement le véhicule concerné au dossier.
                </p>
            </x-carte>

        </div>
    </div>

    <div class="mt-6 flex flex-col-reverse gap-3 border-t border-ardoise-200 pt-5 sm:flex-row sm:justify-center">
        <a href="{{ route('litiges.index') }}" class="btn-secondaire justify-center sm:min-w-[10rem]">Annuler</a>
        <button type="submit" class="btn-primaire justify-center py-3 sm:min-w-[14rem]">Enregistrer le litige</button>
    </div>
    </form>
@endsection
