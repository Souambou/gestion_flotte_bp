@extends('layouts.app')
@section('titre', 'Contrôle du véhicule '.$checklist->vehicule?->immatriculation)
@section('sous-titre', $checklist->vehicule?->libelle.' · contrôle du '.$checklist->date_controle->translatedFormat('l j F Y'))

@section('contenu')
    <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
        <div class="space-y-6 lg:col-span-2">

            <x-carte>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-ardoise-500">Conformité</p>
                        <p class="mt-1 font-display text-2xl font-extrabold text-petro-700">{{ $checklist->taux_conformite }} %</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-ardoise-500">Anomalies</p>
                        <p class="mt-1 font-display text-2xl font-extrabold">{{ $checklist->nombre_anomalies }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-ardoise-500">Kilométrage</p>
                        <p class="mt-1 font-display text-lg font-bold">{{ number_format($checklist->kilometrage, 0, ',', ' ') }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-ardoise-500">Carburant</p>
                        <p class="mt-1 font-display text-lg font-bold">{{ $checklist->niveau_carburant }} %</p>
                    </div>
                </div>
            </x-carte>

            @foreach($rubriques as $rubrique => $points)
                <x-carte :titre="$rubrique" :padding="false">
                    <div class="divide-y divide-ardoise-100">
                        @foreach($points as $cle => $libelle)
                            @php $point = $checklist->points[$cle] ?? null; @endphp
                            <div class="flex items-start justify-between gap-4 px-5 py-3">
                                <div>
                                    <p class="text-sm text-ardoise-800">{{ $libelle }}</p>
                                    @if(! empty($point['commentaire']))
                                        <p class="mt-0.5 text-xs text-ardoise-500">{{ $point['commentaire'] }}</p>
                                    @endif
                                </div>
                                <x-badge :statut="$point['statut'] ?? 'absent'"
                                         :libelle="\App\Models\Checklist::ETATS_POINT[$point['statut'] ?? 'absent'] ?? 'Non renseigné'"/>
                            </div>
                        @endforeach
                    </div>
                </x-carte>
            @endforeach

            @if($checklist->photos)
                <x-carte titre="Photos">
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach($checklist->photos as $photo)
                            <a href="{{ \Illuminate\Support\Facades\Storage::url($photo) }}" target="_blank">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($photo) }}" alt=""
                                     class="h-32 w-full rounded-lg object-cover">
                            </a>
                        @endforeach
                    </div>
                </x-carte>
            @endif
        </div>

        <div class="space-y-6">
            <x-carte titre="Informations du contrôle">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ardoise-500">Date du contrôle</dt>
                        <dd class="mt-0.5 font-semibold">{{ $checklist->date_controle->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ardoise-500">Contrôleur</dt>
                        <dd class="mt-0.5 font-medium">{{ $checklist->auteur?->nom_complet }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ardoise-500">Complétée le</dt>
                        <dd class="mt-0.5 font-medium">{{ $checklist->completee_at?->format('d/m/Y à H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-ardoise-500">État général</dt>
                        <dd class="mt-1"><x-badge :statut="$checklist->etat_general" :libelle="ucfirst($checklist->etat_general)"/></dd>
                    </div>
                </dl>
                <div class="mt-5 space-y-2">
                    <a href="{{ route('checklists.index', ['jour' => $checklist->date_controle->toDateString()]) }}"
                       class="btn-secondaire w-full">Retour aux contrôles du jour</a>
                    @can('checklists.remplir')
                        <a href="{{ route('checklists.create', ['vehicule' => $checklist->vehicule, 'jour' => $checklist->date_controle->toDateString()]) }}"
                           class="btn-fantome w-full">Corriger ce contrôle</a>
                    @endcan
                </div>
            </x-carte>

            @if($checklist->anomalies)
                <x-carte titre="Anomalies constatées">
                    <p class="whitespace-pre-line text-sm text-red-700">{{ $checklist->anomalies }}</p>
                </x-carte>
            @endif

            @if($checklist->commentaire)
                <x-carte titre="Commentaire">
                    <p class="whitespace-pre-line text-sm text-ardoise-700">{{ $checklist->commentaire }}</p>
                </x-carte>
            @endif

            @if($checklist->signature)
                <x-carte titre="Signature">
                    <img src="{{ $checklist->signature }}" alt="Signature du contrôleur" class="w-full rounded-lg border border-ardoise-200">
                </x-carte>
            @endif
        </div>
    </div>
@endsection
