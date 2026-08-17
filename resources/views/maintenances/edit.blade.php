@extends('layouts.app')
@section('titre', $maintenance->intitule)
@section('sous-titre', $maintenance->vehicule?->libelle)

@section('contenu')
    <form method="POST" action="{{ route('maintenances.update', $maintenance) }}">
        @csrf
        @method('PUT')
        @include('maintenances._formulaire', ['vehiculeSelectionne' => null])
    </form>
@endsection
