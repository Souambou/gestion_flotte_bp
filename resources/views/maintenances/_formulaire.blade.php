@php
    $maintenance = $maintenance ?? null;
    $vehiculeParDefaut = $maintenance?->vehicule_id ?? ($vehiculeSelectionne ?? null);
@endphp

<div class="grid gap-6 lg:grid-cols-3 lg:items-start">
    <div class="space-y-6 lg:col-span-2">
        <x-carte titre="Intervention">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-selecteur nom="vehicule_id" libelle="Véhicule" obligatoire
                             :options="$vehicules->pluck('libelle', 'id')" vide="Sélectionner"
                             :valeur="$vehiculeParDefaut"/>
                <x-selecteur nom="type" libelle="Type d'intervention" obligatoire
                             :options="\App\Models\Maintenance::TYPES" :valeur="$maintenance?->type"/>
                <div class="sm:col-span-2">
                    <x-champ nom="intitule" libelle="Intitulé" obligatoire :valeur="$maintenance?->intitule"
                             placeholder="Vidange et remplacement des filtres"/>
                </div>
            </div>

            <div class="mt-5">
                <x-zone-texte nom="description" libelle="Description des travaux" :lignes="4"
                              :valeur="$maintenance?->description"
                              placeholder="Vidange moteur, filtre à huile, filtre à air, contrôle des plaquettes."/>
            </div>
        </x-carte>

        <x-carte titre="Planning et coûts">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-champ nom="date_prevue" libelle="Date prévue" type="date"
                         :valeur="$maintenance?->date_prevue?->format('Y-m-d')"/>
                <x-champ nom="date_realisee" libelle="Date de réalisation" type="date"
                         :valeur="$maintenance?->date_realisee?->format('Y-m-d')"
                         aide="À renseigner une fois l'intervention terminée"/>
                <x-champ nom="kilometrage" libelle="Kilométrage à l'intervention" type="number" min="0"
                         :valeur="$maintenance?->kilometrage"/>
                <x-champ nom="cout" libelle="Coût (FCFA)" type="number" step="1" min="0" :valeur="$maintenance?->cout"/>
                <div class="sm:col-span-2">
                    <x-champ nom="prestataire" libelle="Prestataire" :valeur="$maintenance?->prestataire"
                             placeholder="Garage CFAO Motors — Cotonou"/>
                </div>
            </div>
        </x-carte>
    </div>

    <div class="space-y-6">
        <x-carte titre="Statut">
            <x-selecteur nom="statut" libelle="Avancement" obligatoire
                         :options="\App\Models\Maintenance::STATUTS" :valeur="$maintenance?->statut ?? 'planifiee'"/>
            <p class="mt-3 text-xs leading-relaxed text-ardoise-500">
                Passer une intervention « en cours » bascule automatiquement le véhicule en maintenance :
                il disparaît alors des véhicules réservables. La clôture le rend à nouveau disponible.
            </p>
        </x-carte>

        @if($maintenance)
            @can('maintenances.gerer')
                <form method="POST" action="{{ route('maintenances.destroy', $maintenance) }}"
                      data-confirmer="Cette intervention de maintenance sera supprimée de l'historique du véhicule."
                      data-confirmer-titre="Supprimer l'intervention ?"
                      data-confirmer-bouton="Supprimer" data-confirmer-danger>
                    @csrf
                    @method('DELETE')
                    <button class="btn-fantome w-full !text-red-600">Supprimer cette intervention</button>
                </form>
            @endcan
        @endif
    </div>
</div>
