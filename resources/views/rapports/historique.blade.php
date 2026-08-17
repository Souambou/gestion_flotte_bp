@extends('layouts.app')
@section('titre', 'Historique des réservations')
@section('sous-titre', 'Toutes les demandes de la période, quel que soit leur statut')

@section('contenu')
    @include('rapports._entete', ['rapportExport' => 'historique'])

    <x-carte :padding="false">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-ardoise-100 p-4">
            <input type="hidden" name="debut" value="{{ $kpi->debut()->format('Y-m-d') }}">
            <input type="hidden" name="fin" value="{{ $kpi->fin()->format('Y-m-d') }}">
            <div>
                <label for="statut" class="mb-1 block text-xs font-medium text-ardoise-600">Statut</label>
                <select name="statut" id="statut" class="champ">
                    <option value="">Tous</option>
                    @foreach(\App\Models\Reservation::STATUTS as $cle => $libelle)
                        <option value="{{ $cle }}" @selected(request('statut') === $cle)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn-secondaire">Filtrer</button>
        </form>

        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                <tr><th>Code</th><th>Demandeur</th><th>Trajet</th><th>Période</th><th>Véhicule</th><th>Chauffeur</th><th>Km</th><th>Statut</th></tr>
                </thead>
                <tbody>
                @forelse($reservations as $reservation)
                    <tr class="cursor-pointer" onclick="window.location='{{ route('reservations.show', $reservation) }}'">
                        <td class="font-mono text-xs font-semibold text-petro-700">{{ $reservation->code }}</td>
                        <td>{{ $reservation->demandeur?->nom_complet }}</td>
                        <td class="text-ardoise-600">{{ $reservation->lieu_depart }} → {{ $reservation->lieu_arrivee }}</td>
                        <td class="whitespace-nowrap text-ardoise-600">
                            {{ $reservation->date_debut->format('d/m/Y H:i') }}<br>
                            <span class="text-xs">{{ $reservation->date_fin->format('d/m/Y H:i') }}</span>
                        </td>
                        <td class="font-mono text-xs">{{ $reservation->vehicule?->immatriculation ?? '—' }}</td>
                        <td class="text-ardoise-600">{{ $reservation->chauffeur?->nom_complet ?? 'Sans chauffeur' }}</td>
                        <td>{{ $reservation->deplacement?->distance_parcourue ?? '—' }}</td>
                        <td><x-badge :statut="$reservation->statut" :libelle="$reservation->statut_libelle"/></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-vide titre="Aucune réservation sur la période" message="Élargissez la plage de dates."/></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($reservations->hasPages())
            <div class="border-t border-ardoise-100 p-4">{{ $reservations->links() }}</div>
        @endif
    </x-carte>
@endsection
