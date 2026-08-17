@extends('layouts.app')
@section('titre', 'Changer mon mot de passe')
@section('sous-titre', 'Choisissez un mot de passe robuste et unique')

@section('contenu')
    @if(auth()->user()->doit_changer_mot_de_passe)
        <div class="mb-6 rounded-xl2 border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
            Votre mot de passe est provisoire. Définissez-en un nouveau pour accéder à la plateforme.
        </div>
    @endif

    <form method="POST" action="{{ route('profil.mot-de-passe.update') }}" class="mx-auto max-w-lg space-y-6">
        @csrf
        @method('PUT')

        <x-carte titre="Nouveau mot de passe">
            <div class="space-y-5">
                <x-champ nom="mot_de_passe_actuel" libelle="Mot de passe actuel" type="password" obligatoire
                         autocomplete="current-password"/>
                <x-champ nom="password" libelle="Nouveau mot de passe" type="password" obligatoire
                         autocomplete="new-password" aide="8 caractères minimum, avec au moins une lettre et un chiffre"/>
                <x-champ nom="password_confirmation" libelle="Confirmer le nouveau mot de passe" type="password"
                         obligatoire autocomplete="new-password"/>
            </div>
        </x-carte>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-ardoise-200 pt-5 sm:flex-row sm:justify-center">
            @unless(auth()->user()->doit_changer_mot_de_passe)
                <a href="{{ route('profil.edit') }}" class="btn-secondaire justify-center sm:min-w-[10rem]">Annuler</a>
            @endunless
            <button type="submit" class="btn-primaire justify-center py-3 sm:min-w-[14rem]">Enregistrer</button>
        </div>
    </form>
@endsection
