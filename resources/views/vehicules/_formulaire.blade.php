@php $vehicule = $vehicule ?? null; @endphp

<div class="grid gap-6 lg:grid-cols-3 lg:items-start">
    <div class="space-y-6 lg:col-span-2">

        <x-carte titre="Identification">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-champ nom="immatriculation" libelle="Immatriculation" obligatoire
                         :valeur="$vehicule?->immatriculation" placeholder="AA-1234-RB"/>
                <x-selecteur nom="type" libelle="Type de véhicule" obligatoire
                             :options="\App\Models\Vehicule::TYPES" :valeur="$vehicule?->type"/>
                <x-champ nom="marque" libelle="Marque" obligatoire :valeur="$vehicule?->marque" placeholder="Toyota"/>
                <x-champ nom="modele" libelle="Modèle" obligatoire :valeur="$vehicule?->modele" placeholder="Hilux"/>
                <x-champ nom="annee" libelle="Année de fabrication" type="number" min="1980" max="{{ date('Y') + 1 }}"
                         :valeur="$vehicule?->annee"/>
                <x-selecteur nom="carburant" libelle="Carburant" obligatoire
                             :options="\App\Models\Vehicule::CARBURANTS" :valeur="$vehicule?->carburant"/>
                <x-champ nom="nombre_places" libelle="Nombre de places" type="number" min="1" max="60" obligatoire
                         :valeur="$vehicule?->nombre_places ?? 5"/>
                <x-champ nom="kilometrage" libelle="Kilométrage actuel" type="number" min="0" obligatoire
                         :valeur="$vehicule?->kilometrage ?? 0" aide="En kilomètres"/>
            </div>
        </x-carte>

        <x-carte titre="Documents et échéances" sous-titre="La plateforme alerte automatiquement à l'approche des échéances">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-champ nom="date_mise_en_service" libelle="Mise en service" type="date"
                         :valeur="$vehicule?->date_mise_en_service?->format('Y-m-d')"/>
                <x-champ nom="date_expiration_assurance" libelle="Expiration de l'assurance" type="date"
                         :valeur="$vehicule?->date_expiration_assurance?->format('Y-m-d')"/>
                <x-champ nom="date_visite_technique" libelle="Prochaine visite technique" type="date"
                         :valeur="$vehicule?->date_visite_technique?->format('Y-m-d')"/>
                <x-champ nom="date_prochaine_maintenance" libelle="Prochaine maintenance" type="date"
                         :valeur="$vehicule?->date_prochaine_maintenance?->format('Y-m-d')"/>
                <x-champ nom="km_prochaine_maintenance" libelle="Révision au kilométrage" type="number" min="0"
                         :valeur="$vehicule?->km_prochaine_maintenance"
                         aide="Alerte à {{ config('beninpetro.maintenance.alerte_km') }} km de l'échéance"/>
            </div>
        </x-carte>

        <x-carte titre="Observations">
            <x-zone-texte nom="observations" libelle="Notes internes" :lignes="4" :valeur="$vehicule?->observations"
                          placeholder="Rayure sur l'aile arrière droite, climatisation révisée en mars."/>
        </x-carte>
    </div>

    <div class="space-y-6">
        <x-carte titre="Affectation">
            <div class="space-y-5">
                <x-selecteur nom="statut" libelle="Disponibilité" obligatoire
                             :options="\App\Models\Vehicule::STATUTS" :valeur="$vehicule?->statut ?? 'disponible'"/>
                <x-selecteur nom="agence_id" libelle="Site de rattachement"
                             :options="$agences->pluck('nom', 'id')" vide="Sélectionner un site"
                             :valeur="$vehicule?->agence_id"/>
            </div>
        </x-carte>

        <x-carte titre="Photo">
            @if($vehicule?->photo_url)
                <img src="{{ $vehicule->photo_url }}" alt="" class="mb-4 h-40 w-full rounded-lg object-cover">
            @endif
            <input type="file" name="photo" accept="image/*"
                   class="block w-full text-sm text-ardoise-600 file:mr-3 file:rounded-lg file:border-0 file:bg-petro-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-petro-700 hover:file:bg-petro-100">
            <p class="mt-2 text-xs text-ardoise-500">JPG ou PNG, 4 Mo maximum.</p>
            @error('photo')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
        </x-carte>

    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 border-t border-ardoise-200 pt-5 sm:flex-row sm:justify-center">
    <a href="{{ $vehicule ? route('vehicules.show', $vehicule) : route('vehicules.index') }}"
       class="btn-secondaire justify-center sm:min-w-[10rem]">Annuler</a>
    <button type="submit" class="btn-primaire justify-center py-3 sm:min-w-[14rem]">
        {{ $vehicule ? 'Enregistrer les modifications' : 'Ajouter à la flotte' }}
    </button>
</div>
