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
