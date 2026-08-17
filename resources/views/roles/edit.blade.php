@extends('layouts.app')
@section('titre', config('beninpetro.roles')[$role->name] ?? $role->name)
@section('sous-titre', 'Ajustez les permissions de ce rôle')

@section('contenu')
    <form method="POST" action="{{ route('roles.update', $role) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @if($role->name === 'administrateur')
            <div class="rounded-xl2 border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                Le rôle administrateur conserve par conception l'intégralité des permissions :
                les modifications ci-dessous ne lui sont pas appliquées.
            </div>
        @endif

        <x-carte titre="Permissions accordées"
                 :sous-titre="$role->permissions->count().' permission(s) actuellement attribuée(s)'">
            @include('roles._permissions', ['selectionnees' => $role->permissions->pluck('name')])
        </x-carte>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-ardoise-200 pt-5 sm:flex-row sm:justify-center">
            <a href="{{ route('roles.index') }}" class="btn-secondaire justify-center sm:min-w-[10rem]">Annuler</a>
            <button type="submit" class="btn-primaire justify-center py-3 sm:min-w-[14rem]">Enregistrer les permissions</button>
        </div>
    </form>
@endsection
