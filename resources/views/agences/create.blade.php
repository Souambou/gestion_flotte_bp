@extends('layouts.app')
@section('titre', 'Ajouter un site')

@section('contenu')
    <form method="POST" action="{{ route('agences.store') }}">
        @csrf
        @include('agences._formulaire', ['agence' => null])
    </form>
@endsection
