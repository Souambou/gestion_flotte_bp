@extends('layouts.app')
@section('titre', 'Créer un compte')
@section('sous-titre', 'Le collaborateur reçoit ses identifiants par e-mail')

@section('contenu')
    <form method="POST" action="{{ route('utilisateurs.store') }}">
        @csrf
        @include('utilisateurs._formulaire', ['utilisateur' => null])
    </form>
@endsection
