@extends('layouts.app')
@section('titre', 'Créer un rôle')
@section('sous-titre', 'Définissez un profil d\'accès sur mesure')

@section('contenu')
    <form method="POST" action="{{ route('roles.store') }}" class="space-y-6">
        @csrf

        <x-carte titre="Identifiant du rôle">
            <div class="max-w-md">
                <x-champ nom="name" libelle="Nom technique" obligatoire placeholder="superviseur_regional"
                         aide="Minuscules et tirets bas uniquement. Ce nom est utilisé dans le code."/>
            </div>
        </x-carte>

        <x-carte titre="Permissions accordées">
            @include('roles._permissions')
        </x-carte>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-ardoise-200 pt-5 sm:flex-row sm:justify-center">
            <a href="{{ route('roles.index') }}" class="btn-secondaire justify-center sm:min-w-[10rem]">Annuler</a>
            <button type="submit" class="btn-primaire justify-center py-3 sm:min-w-[14rem]">Créer le rôle</button>
        </div>
    </form>
@endsection
