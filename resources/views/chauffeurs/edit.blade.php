@extends('layouts.app')
@section('titre', 'Modifier '.$chauffeur->nom_complet)

@section('contenu')
    <form method="POST" action="{{ route('chauffeurs.update', $chauffeur) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('chauffeurs._formulaire')
    </form>
@endsection
