@extends('layouts.app')
@section('titre', 'Rôles et permissions')
@section('sous-titre', 'Contrôle d\'accès géré avec Laravel Permission (Spatie)')

@section('contenu')

    <div class="mb-5 flex justify-end">
        <a href="{{ route('roles.create') }}" class="btn-primaire">Créer un rôle</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($roles as $role)
            <x-carte>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-display text-lg font-bold text-ardoise-900">
                            {{ config('beninpetro.roles')[$role->name] ?? ucfirst(str_replace('_', ' ', $role->name)) }}
                        </h3>
                        <p class="font-mono text-xs text-ardoise-500">{{ $role->name }}</p>
                    </div>
                    @if($role->name === 'administrateur')
                        <x-badge ton="vert" libelle="Accès total"/>
                    @endif
                </div>

                <dl class="mt-4 grid grid-cols-2 gap-2 border-t border-ardoise-100 pt-4 text-center">
                    <div>
                        <dt class="text-xs text-ardoise-500">Permissions</dt>
                        <dd class="font-display text-lg font-bold">{{ $role->permissions_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ardoise-500">Utilisateurs</dt>
                        <dd class="font-display text-lg font-bold">{{ $role->users_count }}</dd>
                    </div>
                </dl>

                <div class="mt-4 flex gap-2">
                    <a href="{{ route('roles.edit', $role) }}" class="btn-secondaire flex-1">Permissions</a>
                    @if(! in_array($role->name, array_keys(config('beninpetro.roles'))) && $role->users_count === 0)
                        <form method="POST" action="{{ route('roles.destroy', $role) }}"
                              data-confirmer="Le rôle « {{ $role->name }} » sera supprimé définitivement."
                              data-confirmer-titre="Supprimer ce rôle ?"
                              data-confirmer-bouton="Supprimer" data-confirmer-danger>
                            @csrf
                            @method('DELETE')
                            <button class="btn-fantome !text-red-600">Supprimer</button>
                        </form>
                    @endif
                </div>
            </x-carte>
        @endforeach
    </div>

    <x-carte titre="Permissions disponibles" class="mt-6"
             sous-titre="Vue d'ensemble du référentiel utilisé par la plateforme">
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach($permissions as $module => $listeModule)
                <div>
                    <p class="text-sm font-bold text-ardoise-800">{{ ucfirst($module) }}</p>
                    <ul class="mt-1 space-y-0.5">
                        @foreach($listeModule as $permission)
                            <li class="font-mono text-xs text-ardoise-500">{{ $permission->name }}</li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </x-carte>
@endsection
