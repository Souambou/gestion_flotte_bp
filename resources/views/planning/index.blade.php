@extends('layouts.app')
@section('titre', 'Planning de la flotte')
@section('sous-titre', 'Qui utilise quel véhicule, et quand')

@section('contenu')
    @php
        $nbHeures = $heureFin - $heureDebut;
        $tonParStatut = [
            'en_attente' => ['bg-amber-400/90', 'text-amber-950', 'En attente'],
            'validee'    => ['bg-petro-600',    'text-white',      'Confirmée'],
            'en_cours'   => ['bg-petro-400',    'text-petro-950',  'En cours'],
            'terminee'   => ['bg-ardoise-300',  'text-ardoise-800','Terminée'],
        ];
    @endphp

    {{-- ---------------------------------------------------------------- Disponibilité immédiate --}}
    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        <div class="carte flex items-center gap-4 p-4">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-petro-100 font-display text-xl font-extrabold text-petro-700">
                {{ $libresMaintenant->count() }}
            </span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-ardoise-800">Libres en ce moment</p>
                <p class="truncate text-xs text-ardoise-500">
                    {{ $libresMaintenant->isEmpty() ? 'Aucun véhicule disponible' : $libresMaintenant->take(3)->pluck('immatriculation')->join(', ').($libresMaintenant->count() > 3 ? '…' : '') }}
                </p>
            </div>
        </div>

        <div class="carte flex items-center gap-4 p-4">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-ardoise-100 font-display text-xl font-extrabold text-ardoise-700">
                {{ $vehicules->count() }}
            </span>
            <div>
                <p class="text-sm font-semibold text-ardoise-800">Véhicules dans la flotte</p>
                <p class="text-xs text-ardoise-500">véhicules enregistrés</p>
            </div>
        </div>

        @can('reservations.creer')
            <a href="{{ route('reservations.create') }}" class="carte flex items-center gap-4 p-4 transition hover:border-petro-400">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-petro-600 text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                    </svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-ardoise-800">Réserver un véhicule</p>
                    <p class="text-xs text-ardoise-500">Créneau libre repéré ? Faites votre demande</p>
                </div>
            </a>
        @endcan
    </div>

    {{-- ---------------------------------------------------------------- Barre de navigation --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <a href="{{ route('planning.index', ['vue' => $vue, 'jour' => $vue === 'jour' ? $ancre->copy()->subDay()->toDateString() : $ancre->copy()->subWeek()->toDateString()]) }}"
               class="btn-secondaire !px-3" aria-label="Période précédente">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <a href="{{ route('planning.index', ['vue' => $vue]) }}" class="btn-secondaire">Aujourd'hui</a>
            <a href="{{ route('planning.index', ['vue' => $vue, 'jour' => $vue === 'jour' ? $ancre->copy()->addDay()->toDateString() : $ancre->copy()->addWeek()->toDateString()]) }}"
               class="btn-secondaire !px-3" aria-label="Période suivante">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            <p class="ml-2 font-display text-base font-bold text-ardoise-900">
                @if($vue === 'jour')
                    {{ $ancre->translatedFormat('l j F Y') }}
                @else
                    {{ $debut->translatedFormat('j M') }} – {{ $fin->translatedFormat('j M Y') }}
                @endif
            </p>
        </div>

        <div class="inline-flex overflow-hidden rounded-lg border border-ardoise-300">
            @foreach(['semaine' => 'Semaine', 'jour' => 'Jour'] as $cle => $libelle)
                <a href="{{ route('planning.index', ['vue' => $cle, 'jour' => $ancre->toDateString()]) }}"
                   @class([
                       'px-4 py-2 text-sm font-medium transition',
                       'bg-petro-700 text-white' => $vue === $cle,
                       'bg-white text-ardoise-700 hover:bg-ardoise-50' => $vue !== $cle,
                   ])>{{ $libelle }}</a>
            @endforeach
        </div>
    </div>

    {{-- ---------------------------------------------------------------- Légende --}}
    <div class="mb-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-ardoise-600">
        @foreach($tonParStatut as $classes)
            <span class="flex items-center gap-1.5">
                <span class="h-3 w-3 rounded {{ $classes[0] }}"></span> {{ $classes[2] }}
            </span>
        @endforeach
        <span class="flex items-center gap-1.5">
            <span class="h-3 w-3 rounded bg-white ring-1 ring-inset ring-ardoise-200"></span> Créneau libre
        </span>
    </div>

    {{-- ================================================================ VUE SEMAINE --}}
    @if($vue === 'semaine')
        {{-- Grille horaire : masquée sur mobile au profit de la liste plus bas --}}
        <x-carte :padding="false" class="hidden md:block">
            <div class="overflow-x-auto">
                <div class="min-w-[760px]">
                    {{-- En-tête des jours --}}
                    <div class="grid border-b border-ardoise-200" style="grid-template-columns: 56px repeat({{ $jours->count() }}, minmax(0, 1fr));">
                        <div class="border-r border-ardoise-200 bg-ardoise-50"></div>
                        @foreach($jours as $jour)
                            <div @class([
                                'border-r border-ardoise-200 px-2 py-2.5 text-center last:border-r-0',
                                'bg-petro-50' => $jour->isToday(),
                                'bg-ardoise-50' => ! $jour->isToday(),
                            ])>
                                <p @class([
                                    'text-[11px] font-semibold uppercase tracking-wide',
                                    'text-petro-700' => $jour->isToday(),
                                    'text-ardoise-500' => ! $jour->isToday(),
                                ])>{{ $jour->translatedFormat('D') }}</p>
                                <p @class([
                                    'font-display text-lg font-bold leading-tight',
                                    'text-petro-700' => $jour->isToday(),
                                    'text-ardoise-800' => ! $jour->isToday(),
                                ])>{{ $jour->format('d') }}</p>
                            </div>
                        @endforeach
                    </div>

                    {{-- Corps : une bande horaire par heure --}}
                    <div class="relative grid" style="grid-template-columns: 56px repeat({{ $jours->count() }}, minmax(0, 1fr));">
                        {{-- Colonne des heures --}}
                        <div class="border-r border-ardoise-200 bg-ardoise-50">
                            @for($h = $heureDebut; $h < $heureFin; $h++)
                                <div class="relative h-14 border-b border-ardoise-100 last:border-b-0">
                                    <span class="absolute -top-2 right-1.5 text-[10px] font-medium text-ardoise-400">
                                        {{ sprintf('%02dh', $h) }}
                                    </span>
                                </div>
                            @endfor
                        </div>

                        {{-- Une colonne par jour --}}
                        @foreach($jours as $jour)
                            @php
                                $duJour = $reservations->filter(fn ($r) =>
                                    $r->date_debut->lt($jour->copy()->endOfDay()) &&
                                    $r->date_fin->gt($jour->copy()->startOfDay()));
                            @endphp
                            <div @class([
                                'relative border-r border-ardoise-200 last:border-r-0',
                                'bg-petro-50/40' => $jour->isToday(),
                            ])>
                                {{-- Lignes de fond --}}
                                @for($h = $heureDebut; $h < $heureFin; $h++)
                                    <div class="h-14 border-b border-ardoise-100 last:border-b-0"></div>
                                @endfor

                                {{-- Blocs de réservation --}}
                                @foreach($duJour as $r)
                                    @php
                                        // Bornes du créneau ramenées à la journée puis à la plage affichée.
                                        $d = $r->date_debut->max($jour->copy()->startOfDay());
                                        $f = $r->date_fin->min($jour->copy()->endOfDay());
                                        $hDebut = max($heureDebut, $d->hour + $d->minute / 60);
                                        $hFin = min($heureFin, $f->hour + $f->minute / 60);
                                        if ($f->format('H:i') === '00:00' || $hFin <= $hDebut) { $hFin = min($heureFin, $hDebut + 0.5); }
                                        $haut = ($hDebut - $heureDebut) / $nbHeures * 100;
                                        $hauteur = max(4, ($hFin - $hDebut) / $nbHeures * 100);
                                        $t = $tonParStatut[$r->statut] ?? ['bg-ardoise-300', 'text-ardoise-800', ''];
                                    @endphp
                                    <a href="{{ route('reservations.show', $r) }}"
                                       class="absolute inset-x-1 overflow-hidden rounded px-1.5 py-1 text-[10px] leading-tight shadow-sm transition hover:z-10 hover:brightness-95 {{ $t[0] }} {{ $t[1] }}"
                                       style="top: {{ $haut }}%; height: {{ $hauteur }}%;"
                                       title="{{ $r->vehicule?->immatriculation }} · {{ $r->demandeur?->nom_complet }} ({{ $r->departement_libelle }}) · {{ $r->date_debut->format('H:i') }}–{{ $r->date_fin->format('H:i') }} · {{ $r->statut_libelle }}">
                                        <span class="block truncate font-bold">{{ $r->vehicule?->immatriculation }}</span>
                                        <span class="block truncate opacity-90">{{ $r->demandeur?->prenom }} {{ mb_substr($r->demandeur?->nom, 0, 1) }}.</span>
                                    </a>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-carte>
    @endif

    {{-- ================================================================ VUE JOUR --}}
    @if($vue === 'jour')
        <x-carte :padding="false" class="hidden md:block">
            <div class="overflow-x-auto">
                <div style="min-width: {{ max(760, 120 + $vehicules->count() * 130) }}px;">
                    {{-- En-tête : un véhicule par colonne --}}
                    <div class="grid border-b border-ardoise-200" style="grid-template-columns: 56px repeat({{ max(1, $vehicules->count()) }}, minmax(0, 1fr));">
                        <div class="border-r border-ardoise-200 bg-ardoise-50"></div>
                        @foreach($vehicules as $v)
                            <div class="border-r border-ardoise-200 bg-ardoise-50 px-2 py-2 text-center last:border-r-0">
                                <p class="font-mono text-xs font-bold text-ardoise-800">{{ $v->immatriculation }}</p>
                                <p class="truncate text-[10px] text-ardoise-500">{{ $v->marque }} {{ $v->modele }}</p>
                                @unless($v->est_reservable)
                                    <span class="mt-0.5 inline-block rounded bg-red-100 px-1 text-[9px] font-semibold text-red-700">
                                        {{ $v->statut_libelle }}
                                    </span>
                                @endunless
                            </div>
                        @endforeach
                    </div>

                    <div class="grid" style="grid-template-columns: 56px repeat({{ max(1, $vehicules->count()) }}, minmax(0, 1fr));">
                        <div class="border-r border-ardoise-200 bg-ardoise-50">
                            @for($h = $heureDebut; $h < $heureFin; $h++)
                                <div class="relative h-14 border-b border-ardoise-100 last:border-b-0">
                                    <span class="absolute -top-2 right-1.5 text-[10px] font-medium text-ardoise-400">{{ sprintf('%02dh', $h) }}</span>
                                </div>
                            @endfor
                        </div>

                        @foreach($vehicules as $v)
                            @php $duVehicule = $parVehicule->get($v->id, collect()); @endphp
                            <div @class(['relative border-r border-ardoise-200 last:border-r-0', 'bg-red-50/50' => ! $v->est_reservable])>
                                @for($h = $heureDebut; $h < $heureFin; $h++)
                                    <div class="h-14 border-b border-ardoise-100 last:border-b-0"></div>
                                @endfor

                                @foreach($duVehicule as $r)
                                    @php
                                        $d = $r->date_debut->max($ancre->copy()->startOfDay());
                                        $f = $r->date_fin->min($ancre->copy()->endOfDay());
                                        $hDebut = max($heureDebut, $d->hour + $d->minute / 60);
                                        $hFin = min($heureFin, $f->hour + $f->minute / 60);
                                        if ($hFin <= $hDebut) { $hFin = min($heureFin, $hDebut + 0.5); }
                                        $haut = ($hDebut - $heureDebut) / $nbHeures * 100;
                                        $hauteur = max(5, ($hFin - $hDebut) / $nbHeures * 100);
                                        $t = $tonParStatut[$r->statut] ?? ['bg-ardoise-300', 'text-ardoise-800', ''];
                                    @endphp
                                    <a href="{{ route('reservations.show', $r) }}"
                                       class="absolute inset-x-1 overflow-hidden rounded px-1.5 py-1 text-[10px] leading-tight shadow-sm transition hover:z-10 hover:brightness-95 {{ $t[0] }} {{ $t[1] }}"
                                       style="top: {{ $haut }}%; height: {{ $hauteur }}%;"
                                       title="{{ $r->demandeur?->nom_complet }} ({{ $r->departement_libelle }}) · {{ $r->trajet }} · {{ $r->statut_libelle }}">
                                        <span class="block truncate font-bold">{{ $r->date_debut->format('H:i') }} – {{ $r->date_fin->format('H:i') }}</span>
                                        <span class="block truncate opacity-90">{{ $r->demandeur?->nom_complet }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-carte>
    @endif

    {{-- ================================================================ LISTE (mobile + détail) --}}
    <x-carte :padding="false" class="mt-5 md:mt-6">
        <div class="border-b border-ardoise-100 px-5 py-3">
            <p class="text-sm font-semibold text-ardoise-800">
                Détail des réservations de la période
                <span class="ml-1 font-normal text-ardoise-500">({{ $reservations->count() }})</span>
            </p>
            <p class="mt-0.5 text-xs text-ardoise-500">Qui a réservé, quel véhicule, sur quel créneau.</p>
        </div>

        <div class="divide-y divide-ardoise-100">
            @forelse($reservations->sortBy('date_debut') as $r)
                @php $t = $tonParStatut[$r->statut] ?? ['bg-ardoise-300', 'text-ardoise-800', $r->statut_libelle]; @endphp
                <a href="{{ route('reservations.show', $r) }}" class="flex gap-3 px-4 py-3 transition hover:bg-ardoise-50 sm:px-5">
                    <span class="mt-1 h-full w-1 shrink-0 rounded {{ $t[0] }}" style="min-height: 2.5rem;"></span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-baseline gap-x-2">
                            <span class="font-mono text-sm font-bold text-ardoise-900">{{ $r->vehicule?->immatriculation ?? '—' }}</span>
                            <span class="text-sm text-ardoise-700">{{ $r->demandeur?->nom_complet }}</span>
                            <span class="max-w-[16rem] truncate rounded bg-ardoise-100 px-1.5 py-0.5 text-[10px] font-semibold text-ardoise-600"
                                  title="{{ $r->departement_libelle }}">{{ $r->departement_libelle }}</span>
                        </div>

                        <p class="mt-0.5 text-xs text-ardoise-600">
                            {{ $r->date_debut->translatedFormat('D j M') }} · {{ $r->date_debut->format('H:i') }} → {{ $r->date_fin->format('H:i') }}
                            @if($r->date_debut->toDateString() !== $r->date_fin->toDateString())
                                <span class="text-ardoise-400">({{ $r->date_fin->translatedFormat('D j M') }})</span>
                            @endif
                        </p>
                        <p class="mt-0.5 truncate text-xs text-ardoise-500">{{ $r->trajet }}</p>
                    </div>

                    <x-badge :statut="$r->statut" :libelle="$r->statut_libelle" class="shrink-0 self-start"/>
                </a>
            @empty
                <x-vide titre="Aucune réservation sur cette période"
                        message="Tous les véhicules sont libres. C'est le moment de réserver."/>
            @endforelse
        </div>
    </x-carte>
@endsection
