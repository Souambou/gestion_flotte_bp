@php $chauffeur = $chauffeur ?? null; @endphp

<div class="grid gap-6 lg:grid-cols-3 lg:items-start">
    <div class="space-y-6 lg:col-span-2">
        <x-carte titre="Identité">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-ardoise-700">Matricule</label>
                    <p class="flex h-[42px] items-center rounded-lg border border-ardoise-200 bg-ardoise-50 px-3 font-mono text-sm font-bold text-ardoise-700">
                        {{ $chauffeur?->matricule ?? ($matriculePrevu ?? '—') }}
                    </p>
                    <p class="mt-1 text-xs text-ardoise-500">Attribué automatiquement par la plateforme.</p>
                </div>

                <x-champ nom="date_embauche" libelle="Date d'embauche" type="date" :valeur="$chauffeur?->date_embauche?->format('Y-m-d')"/>
                <x-champ nom="prenom" libelle="Prénom" obligatoire :valeur="$chauffeur?->prenom"/>
                <x-champ nom="nom" libelle="Nom" obligatoire :valeur="$chauffeur?->nom"/>
                <x-champ nom="telephone" libelle="Téléphone" obligatoire :valeur="$chauffeur?->telephone" placeholder="+229 97 00 00 00"/>
                <x-champ nom="email" libelle="Adresse e-mail" type="email" :valeur="$chauffeur?->email"/>
            </div>
        </x-carte>

        <x-carte titre="Permis de conduire" sous-titre="La plateforme alerte {{ config('beninpetro.maintenance.alerte_permis_jours') }} jours avant l'expiration">
            <div class="grid gap-5 sm:grid-cols-3">
                <x-champ nom="numero_permis" libelle="Numéro de permis" obligatoire :valeur="$chauffeur?->numero_permis"/>
                <x-champ nom="categorie_permis" libelle="Catégorie" obligatoire :valeur="$chauffeur?->categorie_permis ?? 'B'"
                         placeholder="B, C, D…"/>
                <x-champ nom="date_expiration_permis" libelle="Expiration" type="date"
                         :valeur="$chauffeur?->date_expiration_permis?->format('Y-m-d')"/>
            </div>
        </x-carte>

        <x-carte titre="Observations">
            <x-zone-texte nom="observations" libelle="Notes internes" :lignes="4" :valeur="$chauffeur?->observations"
                          placeholder="Habilité au transport de produits pétroliers, formation ADR à jour."/>
        </x-carte>
    </div>

    <div class="space-y-6">
        <x-carte titre="Disponibilité">
            <x-selecteur nom="statut" libelle="Statut" obligatoire
                         :options="\App\Models\Chauffeur::STATUTS" :valeur="$chauffeur?->statut ?? 'disponible'"/>
        </x-carte>

        <x-carte titre="Photo">
            @if($chauffeur?->photo_url)
                <img src="{{ $chauffeur->photo_url }}" alt="" class="mb-4 h-32 w-32 rounded-full object-cover">
            @endif
            <input type="file" name="photo" accept="image/*"
                   class="block w-full text-sm text-ardoise-600 file:mr-3 file:rounded-lg file:border-0 file:bg-petro-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-petro-700 hover:file:bg-petro-100">
        </x-carte>

    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 border-t border-ardoise-200 pt-5 sm:flex-row sm:justify-center">
    <a href="{{ $chauffeur ? route('chauffeurs.show', $chauffeur) : route('chauffeurs.index') }}"
       class="btn-secondaire justify-center sm:min-w-[10rem]">Annuler</a>
    <button type="submit" class="btn-primaire justify-center py-3 sm:min-w-[14rem]">
        {{ $chauffeur ? 'Enregistrer les modifications' : 'Ajouter le chauffeur' }}
    </button>
</div>
