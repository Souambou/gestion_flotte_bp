@extends('layouts.app')
@section('titre', 'Mon compte')
@section('sous-titre', $utilisateur->role_libelle.($utilisateur->departement_libelle ? ' · '.$utilisateur->departement_libelle : ''))

@section('contenu')
    <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('profil.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <x-carte titre="Mes informations">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-champ nom="prenom" libelle="Prénom" obligatoire :valeur="$utilisateur->prenom"/>
                        <x-champ nom="nom" libelle="Nom" obligatoire :valeur="$utilisateur->nom"/>
                        <x-champ nom="email" libelle="Adresse e-mail" type="email" obligatoire :valeur="$utilisateur->email"/>
                        <x-champ nom="telephone" libelle="Téléphone" :valeur="$utilisateur->telephone"
                                 placeholder="+229 97 00 00 00" aide="Utilisé pour les notifications SMS"/>
                    </div>

                    <div class="mt-5">
                        <label class="mb-1.5 block text-sm font-medium text-ardoise-700">Photo de profil</label>
                        <div class="flex items-center gap-4">
                            @if($utilisateur->photo)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($utilisateur->photo) }}" alt=""
                                     class="h-16 w-16 rounded-full object-cover">
                            @else
                                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-petro-100 font-display text-lg font-bold text-petro-700">
                                    {{ $utilisateur->initiales }}
                                </span>
                            @endif
                            <input type="file" name="photo" accept="image/*"
                                   class="block flex-1 text-sm text-ardoise-600 file:mr-3 file:rounded-lg file:border-0 file:bg-petro-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-petro-700 hover:file:bg-petro-100">
                        </div>
                    </div>
                </x-carte>

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-ardoise-200 pt-5 sm:flex-row sm:justify-center">
                    <button type="submit" class="btn-primaire justify-center py-3 sm:min-w-[14rem]">Enregistrer mon profil</button>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <x-carte titre="Compte">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ardoise-500">Rôle</dt>
                        <dd class="font-medium">{{ $utilisateur->role_libelle }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ardoise-500">Matricule</dt>
                        <dd class="font-mono font-medium">{{ $utilisateur->matricule ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ardoise-500">Département</dt>
                        <dd class="font-medium">{{ $utilisateur->departement_libelle ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ardoise-500">Dernière connexion</dt>
                        <dd class="font-medium">{{ $utilisateur->derniere_connexion_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
                <p class="mt-4 text-xs leading-relaxed text-ardoise-500">
                    Le rôle et le rattachement sont gérés par l'administrateur de la plateforme.
                </p>
            </x-carte>

            <x-carte titre="Sécurité">
                <a href="{{ route('profil.mot-de-passe') }}" class="btn-secondaire w-full">Changer mon mot de passe</a>
                <form method="POST" action="{{ route('deconnexion') }}" class="mt-3">
                    @csrf
                    <button class="btn-fantome w-full">Me déconnecter</button>
                </form>
            </x-carte>
        </div>
    </div>
@endsection
