@extends('layouts.app')
@section('titre', 'Litiges et réclamations')
@section('sous-titre', 'Signalements liés aux réservations et aux véhicules')

@section('contenu')

    <div class="mb-5 flex flex-wrap items-center gap-2">
        <a href="{{ route('litiges.index') }}"
           @class(['btn-secondaire', '!bg-petro-700 !text-white !border-petro-700' => ! request('statut')])>Tous</a>
        @foreach(\App\Models\Litige::STATUTS as $cle => $libelle)
            <a href="{{ route('litiges.index', ['statut' => $cle]) }}"
               @class(['btn-secondaire', '!bg-petro-700 !text-white !border-petro-700' => request('statut') === $cle])>
                {{ $libelle }} <span class="rounded-full bg-black/10 px-1.5 text-xs">{{ $compteurs[$cle] ?? 0 }}</span>
            </a>
        @endforeach
        <a href="{{ route('litiges.create') }}" class="btn-primaire ml-auto">Déclarer un litige</a>
    </div>

    <x-carte :padding="false">
        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                <tr><th>Référence</th><th>Objet</th><th>Type</th><th>Gravité</th><th>Déclarant</th><th>Déclaré le</th><th>Statut</th><th></th></tr>
                </thead>
                <tbody>
                @forelse($litiges as $litige)
                    <tr class="cursor-pointer" onclick="window.location='{{ route('litiges.show', $litige) }}'">
                        <td class="font-mono text-xs font-semibold text-petro-700">{{ $litige->reference }}</td>
                        <td>
                            <p class="font-medium">{{ $litige->objet }}</p>
                            @if($litige->reservation)
                                <p class="text-xs text-ardoise-500">{{ $litige->reservation?->code }}</p>
                            @endif
                        </td>
                        <td class="text-ardoise-600">{{ $litige->type_libelle }}</td>
                        <td><x-badge :statut="$litige->gravite" :libelle="ucfirst($litige->gravite)"/></td>
                        <td class="text-ardoise-600">{{ $litige->declarant?->nom_complet }}</td>
                        <td class="whitespace-nowrap text-ardoise-600">{{ $litige->created_at->format('d/m/Y') }}</td>
                        <td><x-badge :statut="$litige->statut" :libelle="$litige->statut_libelle"/></td>
                        <td class="text-right"><span class="text-sm font-medium text-petro-700">Ouvrir</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-vide titre="Aucun litige" message="Les réclamations déclarées apparaissent ici avec leur suivi."/></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($litiges->hasPages())
            <div class="border-t border-ardoise-100 p-4">{{ $litiges->links() }}</div>
        @endif
    </x-carte>
@endsection
