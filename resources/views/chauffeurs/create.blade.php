@extends('layouts.app')
@section('titre', 'Ajouter un chauffeur')

@section('contenu')
    <form method="POST" action="{{ route('chauffeurs.store') }}" enctype="multipart/form-data">
        @csrf
        @include('chauffeurs._formulaire', ['chauffeur' => null])
    </form>
@endsection
