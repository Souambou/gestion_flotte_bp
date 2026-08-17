@extends('layouts.app')
@section('titre', 'Modifier '.$utilisateur->nom_complet)

@section('contenu')
    <form method="POST" action="{{ route('utilisateurs.update', $utilisateur) }}">
        @csrf
        @method('PUT')
        @include('utilisateurs._formulaire')
    </form>
@endsection
