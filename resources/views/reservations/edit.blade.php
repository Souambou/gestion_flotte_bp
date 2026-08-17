@extends('layouts.app')
@section('titre', 'Modifier la demande '.$reservation->code)
@section('sous-titre', 'Un changement de créneau renvoie la demande en validation')

@section('contenu')
    <form method="POST" action="{{ route('reservations.update', $reservation) }}" x-data="formulaireReservation()">
        @csrf
        @method('PUT')
        @include('reservations._formulaire')
    </form>
@endsection

@push('scripts')
<script>
    function formulaireReservation() {
        return {
            jourDebut: @json(old('jour_date_debut', $reservation->date_debut->format('Y-m-d'))),
            heureDebut: @json(old('heure_date_debut', $reservation->date_debut->format('H:i'))),
            jourFin: @json(old('jour_date_fin', $reservation->date_fin->format('Y-m-d'))),
            heureFin: @json(old('heure_date_fin', $reservation->date_fin->format('H:i'))),
            resultat: null,

            get debut() { return this.jourDebut && this.heureDebut ? `${this.jourDebut}T${this.heureDebut}` : ''; },
            get fin() { return this.jourFin && this.heureFin ? `${this.jourFin}T${this.heureFin}` : ''; },

            verifierDisponibilite() {
                if (!this.debut || !this.fin || this.fin <= this.debut) {
                    this.resultat = null;
                    return;
                }

                const params = new URLSearchParams({ date_debut: this.debut, date_fin: this.fin });

                fetch(`{{ route('planning.disponibilite') }}?${params}`, { headers: { Accept: 'application/json' } })
                    .then((r) => (r.ok ? r.json() : null))
                    .then((d) => (this.resultat = d))
                    .catch(() => (this.resultat = null));
            },
        };
    }
</script>
@endpush
