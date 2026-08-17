@extends('layouts.app')
@section('titre', 'Avis des utilisateurs')
@section('sous-titre', $total.' évaluation(s) — moyenne générale de '.$moyenne.'/5')

@section('contenu')

    <div class="mb-6 grid gap-6 lg:grid-cols-3">
        <x-carte titre="Satisfaction globale">
            <div class="flex items-center gap-4">
                <p class="font-display text-5xl font-extrabold text-petro-700">{{ $moyenne }}</p>
                <div>
                    <div class="flex gap-0.5">
                        @for($i = 1; $i <= 5; $i++)
                            <svg @class(['h-5 w-5', 'text-lime-400' => $i <= round($moyenne), 'text-ardoise-200' => $i > round($moyenne)])
                                 fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.29 3.97a1 1 0 00.95.69h4.18c.97 0 1.37 1.24.59 1.81l-3.39 2.46a1 1 0 00-.36 1.12l1.3 3.97c.3.92-.76 1.69-1.54 1.12l-3.38-2.46a1 1 0 00-1.18 0l-3.38 2.46c-.79.57-1.84-.2-1.54-1.12l1.29-3.97a1 1 0 00-.36-1.12L2.03 9.4c-.78-.57-.38-1.81.59-1.81h4.18a1 1 0 00.95-.69l1.3-3.97z"/>
                            </svg>
                        @endfor
                    </div>
                    <p class="mt-1 text-sm text-ardoise-500">Sur {{ $total }} évaluation(s)</p>
                </div>
            </div>
        </x-carte>

        <x-carte titre="Répartition des notes" class="lg:col-span-2">
            <div class="space-y-2">
                @for($note = 5; $note >= 1; $note--)
                    @php
                        $nombre = $repartition[$note] ?? 0;
                        $pourcentage = $total > 0 ? round($nombre / $total * 100) : 0;
                    @endphp
                    <a href="{{ route('avis.index', ['note' => $note]) }}" class="flex items-center gap-3 text-sm hover:opacity-80">
                        <span class="w-6 font-semibold text-ardoise-600">{{ $note }}</span>
                        <span class="h-2.5 flex-1 overflow-hidden rounded-full bg-ardoise-100">
                            <span class="block h-full rounded-full bg-petro-500" style="width: {{ $pourcentage }}%"></span>
                        </span>
                        <span class="w-14 text-right text-xs text-ardoise-500">{{ $nombre }} ({{ $pourcentage }}%)</span>
                    </a>
                @endfor
            </div>
        </x-carte>
    </div>

    <x-carte :padding="false">
        <div class="flex items-center justify-between border-b border-ardoise-100 p-4">
            <p class="text-sm font-semibold text-ardoise-700">Derniers retours</p>
            @if(request('note'))
                <a href="{{ route('avis.index') }}" class="text-sm font-medium text-petro-700 hover:underline">Retirer le filtre</a>
            @endif
        </div>

        <div class="divide-y divide-ardoise-100">
            @forelse($avis as $unAvis)
                <div class="p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-display text-lg font-bold text-petro-700">{{ $unAvis->note }}/5</span>
                                <span class="text-sm font-medium text-ardoise-800">{{ $unAvis->auteur?->nom_complet }}</span>
                            </div>
                            <p class="mt-0.5 text-xs text-ardoise-500">
                                <a href="{{ route('reservations.show', $unAvis->reservation) }}" class="font-medium text-petro-700 hover:underline">
                                    {{ $unAvis->reservation?->code }}
                                </a>
                                · {{ $unAvis->reservation?->vehicule?->immatriculation ?? '—' }}
                                @if($unAvis->reservation?->chauffeur) · {{ $unAvis->reservation?->chauffeur->nom_complet }} @endif
                                · {{ $unAvis->created_at->format('d/m/Y') }}
                            </p>
                        </div>

                        <div class="flex gap-2 text-xs">
                            @if($unAvis->note_vehicule)
                                <span class="rounded-lg bg-ardoise-100 px-2 py-1">Véhicule {{ $unAvis->note_vehicule }}/5</span>
                            @endif
                            @if($unAvis->note_chauffeur)
                                <span class="rounded-lg bg-ardoise-100 px-2 py-1">Chauffeur {{ $unAvis->note_chauffeur }}/5</span>
                            @endif
                        </div>
                    </div>

                    @if($unAvis->commentaire)
                        <p class="mt-3 border-l-2 border-petro-200 pl-3 text-sm leading-relaxed text-ardoise-700">
                            {{ $unAvis->commentaire }}
                        </p>
                    @endif
                </div>
            @empty
                <x-vide titre="Aucun avis" message="Les évaluations apparaissent après la clôture des deplacements."/>
            @endforelse
        </div>

        @if($avis->hasPages())
            <div class="border-t border-ardoise-100 p-4">{{ $avis->links() }}</div>
        @endif
    </x-carte>
@endsection
