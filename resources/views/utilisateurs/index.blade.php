@extends('layouts.app')
@section('titre', 'Utilisateurs')
@section('sous-titre', $compteurs['total'].' compte(s) — '.$compteurs['actifs'].' actif(s)')

@section('contenu')

    <x-carte :padding="false">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-ardoise-100 p-4">
            <div class="min-w-[200px] flex-1">
                <label for="q" class="mb-1 block text-xs font-medium text-ardoise-600">Rechercher</label>
                <input type="search" name="q" id="q" value="{{ request('q') }}" class="champ" placeholder="Nom, e-mail ou matricule">
            </div>
            <div>
                <label for="role" class="mb-1 block text-xs font-medium text-ardoise-600">Rôle</label>
                <select name="role" id="role" class="champ">
                    <option value="">Tous</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" @selected(request('role') === $role->name)>
                            {{ config('beninpetro.roles')[$role->name] ?? $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="actif" class="mb-1 block text-xs font-medium text-ardoise-600">État</label>
                <select name="actif" id="actif" class="champ">
                    <option value="">Tous</option>
                    <option value="1" @selected(request('actif') === '1')>Actifs</option>
                    <option value="0" @selected(request('actif') === '0')>Désactivés</option>
                </select>
            </div>
            <button class="btn-secondaire">Filtrer</button>
            @can('utilisateurs.gerer')
                <a href="{{ route('utilisateurs.create') }}" class="btn-primaire ml-auto">Créer un compte</a>
            @endcan
        </form>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                <tr><th>Utilisateur</th><th>Contact</th><th>Rôle</th><th>Département</th><th>Dernière connexion</th><th>État</th><th></th></tr>
                </thead>
                <tbody>
                @forelse($utilisateurs as $utilisateur)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-petro-100 text-xs font-bold text-petro-700">
                                    {{ $utilisateur->initiales }}
                                </span>
                                <div>
                                    <p class="font-medium">{{ $utilisateur->nom_complet }}</p>
                                    <p class="text-xs text-ardoise-500">{{ $utilisateur->poste ?? '—' }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <p class="text-ardoise-700">{{ $utilisateur->email }}</p>
                            <p class="text-xs text-ardoise-500">{{ $utilisateur->telephone ?? '—' }}</p>
                        </td>
                        <td><x-badge ton="vert" :libelle="$utilisateur->role_libelle"/></td>
                        <td class="text-xs text-ardoise-600">{{ $utilisateur->departement_libelle ?? '—' }}</td>
                        <td class="whitespace-nowrap text-ardoise-600">
                            {{ $utilisateur->derniere_connexion_at?->format('d/m/Y H:i') ?? 'Jamais' }}
                        </td>
                        <td>
                            <x-badge :ton="$utilisateur->actif ? 'vert' : 'rouge'"
                                     :libelle="$utilisateur->actif ? 'Actif' : 'Désactivé'"/>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('utilisateurs.show', $utilisateur) }}" class="text-sm font-medium text-petro-700 hover:underline">Ouvrir</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-vide titre="Aucun utilisateur" message="Créez les comptes des commerciaux et des responsables de flotte."/></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($utilisateurs->hasPages())
            <div class="border-t border-ardoise-100 p-4">{{ $utilisateurs->links() }}</div>
        @endif
    </x-carte>
@endsection
