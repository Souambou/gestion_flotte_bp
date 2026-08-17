@extends('layouts.app')
@section('titre', 'Nouvelle demande de véhicule')
@section('sous-titre', 'Le responsable de flotte affecte un véhicule disponible après validation')

@section('contenu')
    <form method="POST" action="{{ route('reservations.store') }}" x-data="formulaireReservation()">
        @csrf
        @include('reservations._formulaire', ['reservation' => null])
    </form>
@endsection

@push('scripts')
<script>
    function formulaireReservation() {
        return {
            jourDebut: @json(old('jour_date_debut', '')),
            heureDebut: @json(old('heure_date_debut', '08:00')),
            jourFin: @json(old('jour_date_fin', '')),
            heureFin: @json(old('heure_date_fin', '17:00')),
            resultat: null,

            // Recompose les deux champs visibles en une valeur exploitable par le serveur.
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
