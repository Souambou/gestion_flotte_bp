@php $utilisateur = $utilisateur ?? null; @endphp

<div class="grid gap-6 lg:grid-cols-3 lg:items-start">
    <div class="space-y-6 lg:col-span-2">
        <x-carte titre="Identité">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-champ nom="prenom" libelle="Prénom" obligatoire :valeur="$utilisateur?->prenom"/>
                <x-champ nom="nom" libelle="Nom" obligatoire :valeur="$utilisateur?->nom"/>
                <x-champ nom="matricule" libelle="Matricule" :valeur="$utilisateur?->matricule" placeholder="BP-0042"/>
                <x-champ nom="poste" libelle="Fonction" :valeur="$utilisateur?->poste" placeholder="Chargé de clientèle"/>
                <x-champ nom="email" libelle="Adresse e-mail professionnelle" type="email" obligatoire
                         :valeur="$utilisateur?->email" placeholder="prenom.nom@beninpetro.bj"/>
                <x-champ nom="telephone" libelle="Téléphone" :valeur="$utilisateur?->telephone"
                         placeholder="+229 97 00 00 00" aide="Utilisé pour les notifications SMS"/>
            </div>
        </x-carte>

        <x-carte titre="Mot de passe"
                 sous-titre="{{ $utilisateur ? 'Laissez vide pour conserver le mot de passe actuel.' : 'Laissez vide pour générer un mot de passe provisoire automatiquement.' }}">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-champ nom="password" libelle="Mot de passe" type="password" autocomplete="new-password"
                         aide="8 caractères minimum, lettres et chiffres"/>
                <x-champ nom="password_confirmation" libelle="Confirmation" type="password" autocomplete="new-password"/>
            </div>
            @if(! $utilisateur)
                <p class="mt-4 rounded-lg bg-ardoise-50 px-4 py-3 text-xs leading-relaxed text-ardoise-600">
                    Si aucun mot de passe n'est saisi, la plateforme en génère un et demande à l'utilisateur
                    de le changer à sa première connexion.
                </p>
            @endif
        </x-carte>
    </div>

    <div class="space-y-6">
        <x-carte titre="Rôle et rattachement">
            <div class="space-y-5">
                <x-selecteur nom="role" libelle="Rôle" obligatoire
                             :options="$roles->mapWithKeys(fn ($r) => [$r->name => config('beninpetro.roles')[$r->name] ?? $r->name])"
                             vide="Sélectionner"
                             :valeur="$utilisateur?->roles->first()?->name"/>
                <x-selecteur nom="departement" libelle="Département" vide="Non rattaché"
                             :options="$departements" :valeur="$utilisateur?->departement"
                             aide="Pré-rempli sur les demandes de réservation de ce collaborateur."/>
                <x-selecteur nom="agence_id" libelle="Site" vide="Non rattaché"
                             :options="$agences->pluck('nom', 'id')" :valeur="$utilisateur?->agence_id"/>
                <x-selecteur nom="actif" libelle="État du compte" obligatoire
                             :options="[1 => 'Actif', 0 => 'Désactivé']"
                             :valeur="$utilisateur ? (int) $utilisateur->actif : 1"/>
            </div>

            <div class="mt-5 space-y-2 border-t border-ardoise-100 pt-4 text-xs leading-relaxed text-ardoise-500">
                <p><strong class="text-ardoise-700">Commercial</strong> — soumet des demandes et suit ses réservations.</p>
                <p><strong class="text-ardoise-700">Responsable de flotte</strong> — valide, affecte, gère véhicules et deplacements.</p>
                <p><strong class="text-ardoise-700">Administrateur</strong> — accès complet, y compris paramètres et comptes.</p>
            </div>
        </x-carte>

    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 border-t border-ardoise-200 pt-5 sm:flex-row sm:justify-center">
    <a href="{{ $utilisateur ? route('utilisateurs.show', $utilisateur) : route('utilisateurs.index') }}"
       class="btn-secondaire justify-center sm:min-w-[10rem]">Annuler</a>
    <button type="submit" class="btn-primaire justify-center py-3 sm:min-w-[14rem]">
        {{ $utilisateur ? 'Enregistrer les modifications' : 'Créer le compte' }}
    </button>
</div>
