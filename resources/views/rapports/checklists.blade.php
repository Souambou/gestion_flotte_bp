@extends('layouts.app')
@section('titre', 'Rapport des contrôles matinaux')
@section('sous-titre', 'Contrôles quotidiens réalisés sur la flotte')

@section('contenu')
    @include('rapports._entete', ['rapportExport' => 'synthese'])

    @php
        $completees = $checklists->filter(fn ($c) => $c->completee_at);
        $conformiteMoyenne = $completees->count() ? round($completees->avg(fn ($c) => $c->taux_conformite)) : 0;
    @endphp

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-statistique libelle="Checklists de la page" :valeur="$checklists->count()"/>
        <x-statistique libelle="Complétées" :valeur="$completees->count()" ton="vert"/>
        <x-statistique libelle="Conformité moyenne" :valeur="$conformiteMoyenne" unite="%" ton="teal"/>
        <x-statistique libelle="Anomalies relevées"
                       :valeur="$completees->sum(fn ($c) => $c->nombre_anomalies)" ton="ambre"/>
    </div>

    <x-carte :padding="false">
        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                <tr><th>Enregistré le</th><th>Véhicule</th><th>Jour contrôlé</th><th>Contrôleur</th><th>Km</th><th>Conformité</th><th>Anomalies</th><th>État</th></tr>
                </thead>
                <tbody>
                @forelse($checklists as $checklist)
                    <tr class="cursor-pointer" onclick="window.location='{{ route('checklists.show', $checklist) }}'">
                        <td class="whitespace-nowrap text-ardoise-600">{{ $checklist->created_at->format('d/m/Y H:i') }}</td>
                        <td class="font-mono text-xs">{{ $checklist->vehicule?->immatriculation }}</td>
                        <td class="text-ardoise-600">{{ $checklist->date_controle->format('d/m/Y') }}</td>
                        <td class="text-ardoise-600">{{ $checklist->auteur?->nom_complet }}</td>
                        <td>{{ number_format($checklist->kilometrage, 0, ',', ' ') }}</td>
                        <td>
                            <span @class([
                                'font-semibold',
                                'text-petro-700' => $checklist->taux_conformite >= 90,
                                'text-amber-600' => $checklist->taux_conformite >= 70 && $checklist->taux_conformite < 90,
                                'text-red-600' => $checklist->taux_conformite < 70,
                            ])>{{ $checklist->taux_conformite }}%</span>
                        </td>
                        <td>{{ $checklist->nombre_anomalies }}</td>
                        <td><x-badge :statut="$checklist->etat_general" :libelle="ucfirst($checklist->etat_general)"/></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-vide titre="Aucun contrôle sur la période" message="Les contrôles apparaissent dès qu'un véhicule est contrôlé."/></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($checklists->hasPages())
            <div class="border-t border-ardoise-100 p-4">{{ $checklists->links() }}</div>
        @endif
    </x-carte>
@endsection
