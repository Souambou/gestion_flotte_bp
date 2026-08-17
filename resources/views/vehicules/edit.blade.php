@extends('layouts.app')
@section('titre', 'Modifier '.$vehicule->immatriculation)

@section('contenu')
    <form method="POST" action="{{ route('vehicules.update', $vehicule) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('vehicules._formulaire')
    </form>
@endsection
