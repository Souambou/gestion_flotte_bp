@extends('layouts.app')
@section('titre', $agence->nom)

@section('contenu')
    <form method="POST" action="{{ route('agences.update', $agence) }}">
        @csrf
        @method('PUT')
        @include('agences._formulaire')
    </form>
@endsection
