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

            // Horloge figée sur l'heure du serveur (fuseau Bénin) au chargement de
            // la page, puis avancée avec le temps réellement écoulé côté client.
            // On ignore ainsi l'horloge et le fuseau du poste de l'utilisateur.
            baseServeurMs: {{ now()->timestamp }} * 1000,
            decalageSecondes: {{ now()->getOffset() }},
            refClient: Date.now(),
            tic: 0,

            init() {
                setInterval(() => { this.tic++; }, 30000);
            },

            get maintenantLocalMs() {
                void this.tic; // dependance reactive : fait revalider dateMin/heureMin chaque tic
                return this.baseServeurMs + this.decalageSecondes * 1000 + (Date.now() - this.refClient);
            },
            get dateMin() {
                const d = new Date(this.maintenantLocalMs);
                return `${d.getUTCFullYear()}-${String(d.getUTCMonth() + 1).padStart(2, '0')}-${String(d.getUTCDate()).padStart(2, '0')}`;
            },
            heureMin(jour) {
                if (jour !== this.dateMin) return '00:00';
                const d = new Date(this.maintenantLocalMs);
                return `${String(d.getUTCHours()).padStart(2, '0')}:${String(d.getUTCMinutes()).padStart(2, '0')}`;
            },

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
