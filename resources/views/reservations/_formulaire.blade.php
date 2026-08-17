{{--
    Formulaire commun à la création et à la modification d'une demande.
    Les actions sont regroupées en bas de page : on remplit, puis on valide.
--}}
@php
    $reservation = $reservation ?? null;
    $departementDefaut = old('departement', $reservation?->departement ?? ($departementParDefaut ?? ''));
    $typeDefaut = old('type_deplacement', $reservation?->type_deplacement ?? 'sortie_simple');
    $avecChauffeur = old('avec_chauffeur', $reservation ? (int) $reservation->avec_chauffeur : 1);
@endphp

<div class="grid gap-6 lg:grid-cols-3 lg:items-start">
    <div class="space-y-6 lg:col-span-2">

        {{-- ------------------------------------------------------------ Quand --}}
        <x-carte titre="Quand ?" sous-titre="Choisissez librement la date et l'heure de départ">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-creneau prefixe="date_debut" libelle="Départ" heure-defaut="08:00"
                           :valeur="$reservation?->date_debut"
                           modele-jour="jourDebut" modele-heure="heureDebut"
                           @change="verifierDisponibilite()"/>
                <x-creneau prefixe="date_fin" libelle="Retour prévu" heure-defaut="17:00"
                           :valeur="$reservation?->date_fin"
                           modele-jour="jourFin" modele-heure="heureFin"
                           @change="verifierDisponibilite()"/>
            </div>

            {{-- Disponibilité vérifiée en direct, sans quitter la page --}}
            <div x-show="resultat" x-cloak class="mt-4 rounded-lg px-4 py-3 text-sm"
                 :class="(resultat?.nombre_libres ?? 0) > 0
                     ? 'border border-petro-200 bg-petro-50'
                     : 'border border-amber-200 bg-amber-50'">
                <p class="font-semibold"
                   :class="(resultat?.nombre_libres ?? 0) > 0 ? 'text-petro-800' : 'text-amber-800'">
                    <span x-text="resultat?.nombre_libres ?? 0"></span>
                    véhicule(s) libre(s) sur ce créneau.
                </p>
                <p class="mt-1 text-xs" :class="(resultat?.nombre_libres ?? 0) > 0 ? 'text-petro-700' : 'text-amber-700'">
                    Information indicative : l'affectation définitive revient au responsable de flotte.
                </p>
            </div>
        </x-carte>

        {{-- ------------------------------------------------------------ Où --}}
        <x-carte titre="Où ?" sous-titre="Indiquez des lieux précis pour faciliter l'organisation">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-champ nom="lieu_depart" libelle="Lieu de départ" obligatoire
                         :valeur="$reservation?->lieu_depart"
                         placeholder="Siège Bénin Pétro, Cotonou"/>
                <x-champ nom="lieu_arrivee" libelle="Destination" obligatoire
                         :valeur="$reservation?->lieu_arrivee"
                         placeholder="Dépôt de Parakou"/>
            </div>

            @unless($mapsActif ?? false)
                <p class="mt-3 text-xs text-ardoise-500">
                    L'estimation automatique des distances s'activera dès que la clé Google Maps
                    sera renseignée dans Paramètres&nbsp;› Intégrations.
                </p>
            @endunless
        </x-carte>

        {{-- ------------------------------------------------- Nature de la demande --}}
        <x-carte titre="Nature de la demande">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-selecteur nom="departement" libelle="Département demandeur" obligatoire
                             :options="$departements" vide="Sélectionner un département"
                             :valeur="$departementDefaut"
                             aide="Sert aux statistiques de demandes par service."/>

                <x-selecteur nom="type_deplacement" libelle="Type de déplacement" obligatoire
                             :options="$typesDeplacement" :valeur="$typeDefaut"/>
            </div>

            <fieldset class="mt-5">
                <legend class="mb-2 text-sm font-medium text-ardoise-700">
                    Conduite <span class="text-red-500">*</span>
                </legend>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-ardoise-200 p-4 transition hover:border-petro-400 has-[:checked]:border-petro-500 has-[:checked]:bg-petro-50">
                        <input type="radio" name="avec_chauffeur" value="1" @checked((int) $avecChauffeur === 1)
                               class="mt-0.5 text-petro-600 focus:ring-petro-500">
                        <span>
                            <span class="block text-sm font-semibold">Avec chauffeur</span>
                            <span class="block text-xs text-ardoise-500">Un chauffeur de la société est affecté au déplacement.</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-ardoise-200 p-4 transition hover:border-petro-400 has-[:checked]:border-petro-500 has-[:checked]:bg-petro-50">
                        <input type="radio" name="avec_chauffeur" value="0" @checked((int) $avecChauffeur === 0)
                               class="mt-0.5 text-petro-600 focus:ring-petro-500">
                        <span>
                            <span class="block text-sm font-semibold">Sans chauffeur</span>
                            <span class="block text-xs text-ardoise-500">Vous conduisez vous-même ; permis en cours de validité exigé.</span>
                        </span>
                    </label>
                </div>
            </fieldset>
        </x-carte>

        {{-- ------------------------------------------------------------ Motif --}}
        <x-carte titre="Pourquoi ?">
            <x-zone-texte nom="motif" libelle="Motif du déplacement" obligatoire :lignes="4"
                          :valeur="$reservation?->motif"
                          placeholder="Tournée commerciale sur l'axe Cotonou — Parakou, livraison de documents au dépôt."
                          aide="Ce motif est lu par le responsable de flotte lors de l'arbitrage entre demandes."/>
        </x-carte>
    </div>

    {{-- ------------------------------------------------------------ Colonne d'aide --}}
    <div class="space-y-6">
        <x-carte titre="Comment ça se passe">
            <ol class="space-y-3 text-sm text-ardoise-600">
                @foreach([
                    'Votre demande part en attente de validation.',
                    'Le responsable de flotte affecte un véhicule disponible.',
                    'Vous recevez la décision par e-mail et notification.',
                    'En cas de refus, une alternative vous est proposée.',
                ] as $i => $etape)
                    <li class="flex gap-3">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-petro-100 text-[11px] font-bold text-petro-700">{{ $i + 1 }}</span>
                        {{ $etape }}
                    </li>
                @endforeach
            </ol>
            <p class="mt-4 border-t border-ardoise-100 pt-3 text-xs text-ardoise-500">
                Annulation libre jusqu'à {{ config('beninpetro.reservation.delai_annulation_heures') }} h avant le départ.
            </p>
        </x-carte>

        <x-carte titre="Vérifier les disponibilités">
            <p class="text-sm text-ardoise-600">
                Le planning montre, jour par jour, les créneaux déjà réservés par vos collègues.
            </p>
            <a href="{{ route('planning.index') }}" target="_blank" rel="noopener" class="btn-secondaire mt-4 w-full">
                Ouvrir le planning
            </a>
        </x-carte>
    </div>
</div>

{{-- =================================================== Actions, en bas de page --}}
<div class="mt-6 flex flex-col-reverse gap-3 border-t border-ardoise-200 pt-5 sm:flex-row sm:justify-center">
    <a href="{{ $reservation ? route('reservations.show', $reservation) : route('reservations.index') }}"
       class="btn-secondaire justify-center sm:min-w-[10rem]">Annuler</a>

    <button type="submit" class="btn-primaire justify-center py-3 sm:min-w-[14rem]">
        {{ $reservation ? 'Enregistrer les modifications' : 'Envoyer la demande' }}
    </button>
</div>
