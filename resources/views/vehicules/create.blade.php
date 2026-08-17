@extends('layouts.app')
@section('titre', 'Ajouter un véhicule')
@section('sous-titre', 'Renseignez la carte d\'identité du véhicule et ses échéances documentaires')

@section('contenu')
    <form method="POST" action="{{ route('vehicules.store') }}" enctype="multipart/form-data">
        @csrf
        @include('vehicules._formulaire', ['vehicule' => null])
    </form>
@endsection
