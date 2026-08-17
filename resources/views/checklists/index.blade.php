@extends('layouts.app')
@section('titre', 'Contrôle matinal de la flotte')
@section('sous-titre', 'État des véhicules constaté chaque matin')

@section('contenu')
    @php $total = $controles->count() + $restants->count(); @endphp

    {{-- ------------------------------------------------------ Navigation par jour --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <a href="{{ route('checklists.index', ['jour' => $jour->copy()->subDay()->toDateString()]) }}"
               class="btn-secondaire !px-3" aria-label="Jour précédent">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>

            @unless($jour->isToday())
                <a href="{{ route('checklists.index') }}" class="btn-secondaire">Aujourd'hui</a>
                <a href="{{ route('checklists.index', ['jour' => $jour->copy()->addDay()->toDateString()]) }}"
                   class="btn-secondaire !px-3" aria-label="Jour suivant">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @endunless

            <p class="ml-1 font-display text-base font-bold text-ardoise-900">
                {{ $jour->translatedFormat('l j F Y') }}
                @if($jour->isToday())
                    <span class="ml-1 rounded bg-petro-100 px-2 py-0.5 text-xs font-semibold text-petro-700">Aujourd'hui</span>
                @endif
            </p>
        </div>

        <input type="date" max="{{ now()->toDateString() }}" value="{{ $jour->toDateString() }}"
               class="champ sm:max-w-[11rem]"
               onchange="window.location = '{{ route('checklists.index') }}?jour=' + this.value"
               aria-label="Choisir une date de contrôle">
    </div>

    {{-- ------------------------------------------------------ Avancement du jour --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-statistique libelle="Véhicules contrôlés" :valeur="$controles->count()"
                       :variation="$total ? 'sur '.$total.' véhicule(s)' : null" ton="vert"/>
        <x-statistique libelle="Reste à contrôler" :valeur="$restants->count()"
                       :ton="$restants->isEmpty() ? 'vert' : 'ambre'"/>
        <x-statistique libelle="Conformité moyenne" :valeur="$conformiteMoyenne" unite="%" ton="teal"/>
        <x-statistique libelle="Anomalies relevées" :valeur="$anomalies"
                       :ton="$anomalies ? 'rouge' : 'ardoise'"/>
    </div>

    @if($total)
        @php $avancement = round($controles->count() / max(1, $total) * 100); @endphp
        <div class="mb-6">
            <div class="mb-1.5 flex items-baseline justify-between text-xs">
                <span class="font-medium text-ardoise-600">Avancement du contrôle</span>
                <span class="font-semibold text-petro-700">{{ $avancement }} %</span>
            </div>
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-ardoise-200">
                <div class="h-full rounded-full bg-petro-500 transition-all" style="width: {{ $avancement }}%"></div>
            </div>
        </div>
    @endif

    {{-- ------------------------------------------------------ Reste à contrôler --}}
    @if($restants->isNotEmpty())
        <x-carte titre="À contrôler" :sous-titre="$restants->count().' véhicule(s) n\'ont pas encore été contrôlés ce jour'" class="mb-6">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($restants as $vehicule)
                    <div class="flex items-center justify-between gap-3 rounded-xl2 border border-amber-200 bg-amber-50 p-3">
                        <div class="min-w-0">
                            <p class="font-mono text-sm font-bold text-ardoise-900">{{ $vehicule->immatriculation }}</p>
                            <p class="truncate text-xs text-ardoise-600">{{ $vehicule->marque }} {{ $vehicule->modele }}</p>
                        </div>
                        @can('checklists.remplir')
                            <a href="{{ route('checklists.create', ['vehicule' => $vehicule, 'jour' => $jour->toDateString()]) }}"
                               class="btn-primaire shrink-0 !py-1.5 text-xs">Contrôler</a>
                        @endcan
                    </div>
                @endforeach
            </div>
        </x-carte>
    @else
        <div class="mb-6 flex items-center gap-3 rounded-xl2 border border-petro-200 bg-petro-50 px-5 py-4">
            <svg class="h-6 w-6 shrink-0 text-petro-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <p class="text-sm font-medium text-petro-800">
                Toute la flotte a été contrôlée pour cette journée.
            </p>
        </div>
    @endif

    {{-- ------------------------------------------------------ Contrôles réalisés --}}
    <x-carte titre="Contrôles réalisés" :padding="false">
        <div class="divide-y divide-ardoise-100">
            @forelse($controles as $controle)
                <a href="{{ route('checklists.show', $controle) }}"
                   class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3 transition hover:bg-ardoise-50 sm:px-5">
                    <div class="min-w-0 flex-1">
                        <p class="font-mono text-sm font-bold text-ardoise-900">{{ $controle->vehicule?->immatriculation }}</p>
                        <p class="truncate text-xs text-ardoise-500">
                            {{ $controle->vehicule?->marque }} {{ $controle->vehicule?->modele }} ·
                            contrôlé par {{ $controle->auteur?->nom_complet }}
                            à {{ $controle->completee_at?->format('H:i') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 sm:gap-4">
                        <div class="text-right">
                            <p class="text-[10px] uppercase tracking-wide text-ardoise-400">Kilométrage</p>
                            <p class="text-xs font-semibold">{{ number_format($controle->kilometrage, 0, ',', ' ') }} km</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] uppercase tracking-wide text-ardoise-400">Carburant</p>
                            <p class="text-xs font-semibold">{{ $controle->niveau_carburant }} %</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] uppercase tracking-wide text-ardoise-400">Conformité</p>
                            <p @class([
                                'text-xs font-bold',
                                'text-petro-700' => $controle->taux_conformite >= 90,
                                'text-amber-600' => $controle->taux_conformite >= 70 && $controle->taux_conformite < 90,
                                'text-red-600' => $controle->taux_conformite < 70,
                            ])>{{ $controle->taux_conformite }} %</p>
                        </div>

                        @if($controle->nombre_anomalies)
                            <span class="rounded-lg bg-red-100 px-2 py-1 text-[11px] font-semibold text-red-700">
                                {{ $controle->nombre_anomalies }} anomalie(s)
                            </span>
                        @endif

                        <x-badge :statut="$controle->etat_general" :libelle="ucfirst($controle->etat_general)"/>
                    </div>
                </a>
            @empty
                <x-vide titre="Aucun contrôle enregistré"
                        message="Les contrôles réalisés sur les véhicules apparaîtront ici."/>
            @endforelse
        </div>
    </x-carte>
@endsection
