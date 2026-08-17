@extends('layouts.app')
@section('titre', 'Planifier une intervention')

@section('contenu')
    <form method="POST" action="{{ route('maintenances.store') }}">
        @csrf
        @include('maintenances._formulaire', ['maintenance' => null])
    </form>
@endsection
