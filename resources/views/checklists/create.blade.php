@extends('layouts.app')
@section('titre', 'Contrôle du véhicule '.$vehicule->immatriculation)
@section('sous-titre', 'Contrôle du '.$jour->translatedFormat('l j F Y'))

@section('contenu')
    <form method="POST" action="{{ route('checklists.store', $vehicule) }}"
          enctype="multipart/form-data" x-data="controleVehicule()">
        @csrf
        <input type="hidden" name="jour" value="{{ $jour->toDateString() }}">

        @if($checklist)
            <div class="mb-5 flex items-start gap-3 rounded-xl2 border border-amber-200 bg-amber-50 px-4 py-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" d="M12 8v5m0 3h.01"/>
                </svg>
                <p class="text-sm text-amber-800">
                    Ce véhicule a déjà été contrôlé le {{ $jour->format('d/m/Y') }}
                    par {{ $checklist->auteur?->nom_complet }}. Enregistrer à nouveau remplacera le contrôle existant.
                </p>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
            <div class="space-y-6 lg:col-span-2">

                {{-- ------------------------------------------------------ Relevés --}}
                <x-carte titre="Relevés du matin">
                    <div class="grid gap-5 sm:grid-cols-3">
                        <x-champ nom="kilometrage" libelle="Kilométrage" type="number" min="0" obligatoire
                                 :valeur="$checklist?->kilometrage ?? $vehicule->kilometrage"
                                 aide="Le relevé met à jour le compteur du véhicule."/>
                        <x-champ nom="niveau_carburant" libelle="Niveau de carburant (%)" type="number"
                                 min="0" max="100" obligatoire :valeur="$checklist?->niveau_carburant ?? 100"/>
                        <x-selecteur nom="etat_general" libelle="État général" obligatoire
                                     :options="['bon' => 'Bon', 'moyen' => 'Moyen', 'mauvais' => 'Mauvais']"
                                     :valeur="$checklist?->etat_general ?? 'bon'"/>
                    </div>
                </x-carte>

                {{-- ------------------------------------------------ Points de contrôle --}}
                @foreach($rubriques as $rubrique => $points)
                    @php $ancre = \Illuminate\Support\Str::slug($rubrique); @endphp
                    <x-carte :titre="$rubrique">
                        <x-slot:action>
                            <button type="button" @click="toutConforme('{{ $ancre }}')"
                                    class="text-xs font-medium text-petro-700 hover:underline">
                                Tout marquer conforme
                            </button>
                        </x-slot:action>

                        <div class="divide-y divide-ardoise-100">
                            @foreach($points as $cle => $libelle)
                                @php $valeur = $checklist?->points[$cle] ?? null; @endphp
                                <div class="grid gap-3 py-3 sm:grid-cols-[1fr_auto] sm:items-center"
                                     data-rubrique="{{ $ancre }}">
                                    <div>
                                        <p class="text-sm font-medium text-ardoise-800">{{ $libelle }}</p>
                                        <input type="text" name="points[{{ $cle }}][commentaire]"
                                               value="{{ old("points.$cle.commentaire", $valeur['commentaire'] ?? '') }}"
                                               placeholder="Observation (facultatif)"
                                               class="champ mt-1.5 !py-1.5 text-xs">
                                    </div>

                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach(\App\Models\Checklist::ETATS_POINT as $etat => $libelleEtat)
                                            <label class="cursor-pointer rounded-lg border border-ardoise-200 px-2.5 py-1.5 text-xs font-medium transition hover:border-petro-400">
                                                <input type="radio" name="points[{{ $cle }}][statut]" value="{{ $etat }}"
                                                       class="peer sr-only"
                                                       @checked(old("points.$cle.statut", $valeur['statut'] ?? 'conforme') === $etat)>
                                                <span @class([
                                                    'peer-checked:font-bold',
                                                    'peer-checked:text-petro-700' => $etat === 'conforme',
                                                    'peer-checked:text-amber-700' => $etat === 'a_surveiller',
                                                    'peer-checked:text-red-700' => in_array($etat, ['non_conforme', 'absent']),
                                                ])>{{ $libelleEtat }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-carte>
                @endforeach

                {{-- ------------------------------------------------ Anomalies et photos --}}
                <x-carte titre="Anomalies et photos">
                    <x-zone-texte nom="anomalies" libelle="Anomalies constatées" :lignes="3"
                                  :valeur="$checklist?->anomalies"
                                  placeholder="Feu arrière droit défectueux, pneu avant gauche à surveiller."
                                  aide="Une anomalie signalée ici remonte dans le rapport des contrôles."/>

                    <div class="mt-5">
                        <label for="photos" class="mb-1.5 block text-sm font-medium text-ardoise-700">
                            Photos de l'état du véhicule
                        </label>
                        <input type="file" name="photos[]" id="photos" accept="image/*" multiple
                               class="block w-full text-sm text-ardoise-600 file:mr-3 file:rounded-lg file:border-0 file:bg-petro-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-petro-700 hover:file:bg-petro-100">
                        <p class="mt-1 text-xs text-ardoise-500">Quatre angles du véhicule recommandés. 4 Mo par photo.</p>
                    </div>

                    <div class="mt-5">
                        <x-zone-texte nom="commentaire" libelle="Commentaire général" :lignes="3"
                                      :valeur="$checklist?->commentaire"/>
                    </div>
                </x-carte>

                {{-- ------------------------------------------------------ Signature --}}
                <x-carte titre="Signature du contrôleur" sous-titre="Signez dans le cadre pour valider le contrôle">
                    <canvas x-ref="signature" height="160"
                            class="w-full cursor-crosshair touch-none rounded-lg border border-dashed border-ardoise-300 bg-white"
                            @pointerdown.prevent="commencer($event)" @pointermove.prevent="tracer($event)"
                            @pointerup="terminer()" @pointerleave="terminer()"></canvas>
                    <input type="hidden" name="signature" x-ref="champSignature" value="{{ $checklist?->signature }}">
                    <button type="button" @click="effacer()" class="btn-fantome mt-2 text-xs">Effacer la signature</button>
                </x-carte>
            </div>

            {{-- ------------------------------------------------------ Colonne latérale --}}
            <div class="space-y-6">
                <x-carte titre="Véhicule contrôlé">
                    @if($vehicule->photo_url)
                        <img src="{{ $vehicule->photo_url }}" alt="" class="mb-4 h-32 w-full rounded-lg object-cover">
                    @endif
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-ardoise-500">Immatriculation</dt>
                            <dd class="mt-0.5 font-mono font-bold">{{ $vehicule->immatriculation }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-ardoise-500">Modèle</dt>
                            <dd class="mt-0.5 font-medium">{{ $vehicule->marque }} {{ $vehicule->modele }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-ardoise-500">Site</dt>
                            <dd class="mt-0.5 font-medium">{{ $vehicule->agence?->nom ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-ardoise-500">Contrôleur</dt>
                            <dd class="mt-0.5 font-medium">{{ auth()->user()->nom_complet }}</dd>
                        </div>
                    </dl>
                </x-carte>

                <x-carte titre="Rappel">
                    <p class="text-sm leading-relaxed text-ardoise-600">
                        Le contrôle se fait chaque matin sur l'ensemble de la flotte, qu'un déplacement
                        soit prévu ou non. Il constitue la trace de l'état du véhicule pour la journée.
                    </p>
                </x-carte>
            </div>
        </div>

        {{-- ------------------------------------------------- Actions, en bas de page --}}
        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-ardoise-200 pt-5 sm:flex-row sm:justify-center">
            <a href="{{ route('checklists.index', ['jour' => $jour->toDateString()]) }}"
               class="btn-secondaire justify-center sm:min-w-[10rem]">Annuler</a>
            <button type="submit" class="btn-primaire justify-center py-3 sm:min-w-[14rem]">
                Enregistrer le contrôle
            </button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    function controleVehicule() {
        return {
            dessine: false,
            contexte: null,

            init() {
                const canvas = this.$refs.signature;
                canvas.width = canvas.offsetWidth;
                this.contexte = canvas.getContext('2d');
                this.contexte.lineWidth = 2;
                this.contexte.lineCap = 'round';
                this.contexte.strokeStyle = '#01582D';

                // Réaffiche une signature déjà enregistrée.
                const existante = this.$refs.champSignature.value;
                if (existante) {
                    const img = new Image();
                    img.onload = () => this.contexte.drawImage(img, 0, 0, canvas.width, canvas.height);
                    img.src = existante;
                }
            },

            position(e) {
                const r = this.$refs.signature.getBoundingClientRect();
                return { x: e.clientX - r.left, y: e.clientY - r.top };
            },

            commencer(e) {
                this.dessine = true;
                const p = this.position(e);
                this.contexte.beginPath();
                this.contexte.moveTo(p.x, p.y);
            },

            tracer(e) {
                if (!this.dessine) return;
                const p = this.position(e);
                this.contexte.lineTo(p.x, p.y);
                this.contexte.stroke();
            },

            terminer() {
                if (!this.dessine) return;
                this.dessine = false;
                this.$refs.champSignature.value = this.$refs.signature.toDataURL('image/png');
            },

            effacer() {
                const c = this.$refs.signature;
                this.contexte.clearRect(0, 0, c.width, c.height);
                this.$refs.champSignature.value = '';
            },

            toutConforme(rubrique) {
                document.querySelectorAll(`[data-rubrique="${rubrique}"] input[value="conforme"]`)
                    .forEach((input) => (input.checked = true));
                notifier.info('Rubrique marquée conforme.');
            },
        };
    }
</script>
@endpush
