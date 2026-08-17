@php $agence = $agence ?? null; @endphp

<div class="mx-auto max-w-2xl space-y-6">
    <x-carte titre="Coordonnées du site">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-champ nom="nom" libelle="Nom du site" obligatoire :valeur="$agence?->nom" placeholder="Siège Cotonou"/>
            <x-champ nom="ville" libelle="Ville" obligatoire :valeur="$agence?->ville" placeholder="Cotonou"/>
            <div class="sm:col-span-2">
                <x-champ nom="adresse" libelle="Adresse" :valeur="$agence?->adresse"
                         placeholder="Boulevard de la Marina, Cotonou"/>
            </div>
            <x-champ nom="telephone" libelle="Téléphone" :valeur="$agence?->telephone" placeholder="+229 21 00 00 00"/>
            <x-selecteur nom="active" libelle="État" obligatoire
                         :options="[1 => 'Actif', 0 => 'Inactif']" :valeur="$agence ? (int) $agence->active : 1"/>
        </div>
    </x-carte>

    <x-carte titre="Géolocalisation" sous-titre="Facultatif — utilisé pour le calcul des distances">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-champ nom="latitude" libelle="Latitude" type="number" step="0.0000001" :valeur="$agence?->latitude"
                     placeholder="6.3654"/>
            <x-champ nom="longitude" libelle="Longitude" type="number" step="0.0000001" :valeur="$agence?->longitude"
                     placeholder="2.4183"/>
        </div>
    </x-carte>

    <div class="mt-6 flex flex-col-reverse gap-3 border-t border-ardoise-200 pt-5 sm:flex-row sm:justify-center">
        <a href="{{ route('agences.index') }}" class="btn-secondaire justify-center sm:min-w-[10rem]">Annuler</a>
        <button type="submit" class="btn-primaire justify-center py-3 sm:min-w-[14rem]">
            {{ $agence ? 'Enregistrer les modifications' : 'Créer le site' }}
        </button>
    </div>

    @if($agence)
        @can('agences.gerer')
            <form method="POST" action="{{ route('agences.destroy', $agence) }}" class="mt-4 text-center"
                  data-confirmer="Le site « {{ $agence->nom }} » sera supprimé. Les véhicules qui y sont rattachés ne le seront plus."
                  data-confirmer-titre="Supprimer ce site ?"
                  data-confirmer-bouton="Supprimer" data-confirmer-danger>
                @csrf
                @method('DELETE')
                <button class="btn-fantome mx-auto !text-red-600">Supprimer ce site</button>
            </form>
        @endcan
    @endif
</div>
