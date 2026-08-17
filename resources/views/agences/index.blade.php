@extends('layouts.app')
@section('titre', 'Sites et agences')
@section('sous-titre', 'Lieux de rattachement des véhicules, chauffeurs et collaborateurs')

@section('contenu')

    @can('agences.gerer')
        <div class="mb-5 flex justify-end">
            <a href="{{ route('agences.create') }}" class="btn-primaire">Ajouter un site</a>
        </div>
    @endcan

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse($agences as $agence)
            <x-carte>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-display text-lg font-bold text-ardoise-900">{{ $agence->nom }}</h3>
                        <p class="text-sm text-ardoise-500">{{ $agence->ville }}</p>
                    </div>
                    <x-badge :ton="$agence->active ? 'vert' : 'ardoise'" :libelle="$agence->active ? 'Actif' : 'Inactif'"/>
                </div>

                @if($agence->adresse)
                    <p class="mt-3 text-sm text-ardoise-600">{{ $agence->adresse }}</p>
                @endif
                @if($agence->telephone)
                    <p class="mt-1 text-sm text-ardoise-600">{{ $agence->telephone }}</p>
                @endif

                <dl class="mt-4 grid grid-cols-3 gap-2 border-t border-ardoise-100 pt-4 text-center">
                    <div>
                        <dt class="text-xs text-ardoise-500">Véhicules</dt>
                        <dd class="font-display text-lg font-bold">{{ $agence->vehicules_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ardoise-500">Chauffeurs</dt>
                        <dd class="font-display text-lg font-bold">{{ $agence->chauffeurs_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ardoise-500">Utilisateurs</dt>
                        <dd class="font-display text-lg font-bold">{{ $agence->utilisateurs_count }}</dd>
                    </div>
                </dl>

                @can('agences.gerer')
                    <a href="{{ route('agences.edit', $agence) }}" class="btn-secondaire mt-4 w-full">Modifier</a>
                @endcan
            </x-carte>
        @empty
            <div class="sm:col-span-2 xl:col-span-3">
                <x-vide titre="Aucun site enregistré" message="Créez au moins un site pour rattacher les véhicules et les chauffeurs."/>
            </div>
        @endforelse
    </div>

    @if($agences->hasPages())
        <div class="mt-6">{{ $agences->links() }}</div>
    @endif
@endsection
