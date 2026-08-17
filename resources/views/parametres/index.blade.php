@extends('layouts.app')
@section('titre', 'Paramètres de la plateforme')
@section('sous-titre', 'Clés API, notifications et règles métier — modifiables sans intervention technique')

@section('contenu')
    @php
        $libellesGroupes = [
            'integrations' => ['Intégrations et clés API', 'Connectez Google Maps et la passerelle SMS. Les clés sont chiffrées en base et ne sont jamais réaffichées en clair.'],
            'notifications' => ['Notifications', 'Canaux utilisés pour les confirmations, rappels et alertes.'],
            'reservation' => ['Règles de réservation', 'Contraintes appliquées automatiquement à chaque demande.'],
            'general' => ['Identité de la société', 'Informations reprises dans les e-mails et les rapports PDF.'],
        ];
    @endphp

    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <div @class([
            'carte flex items-center justify-between gap-4 p-5',
            'border-petro-300 bg-petro-50' => $mapsActif,
        ])>
            <div>
                <p class="text-sm font-semibold text-ardoise-800">Google Maps</p>
                <p class="text-xs text-ardoise-500">
                    {{ $mapsActif ? 'Clé enregistrée — cartes et distances actives.' : 'Aucune clé enregistrée : les cartes sont masquées.' }}
                </p>
            </div>
            <form method="POST" action="{{ route('parametres.test-maps') }}">
                @csrf
                <button class="btn-secondaire whitespace-nowrap">Tester</button>
            </form>
        </div>

        <div class="carte flex items-center justify-between gap-4 p-5">
            <div>
                <p class="text-sm font-semibold text-ardoise-800">Passerelle SMS</p>
                <p class="text-xs text-ardoise-500">Envoie un SMS de test vers votre numéro de profil.</p>
            </div>
            <form method="POST" action="{{ route('parametres.test-sms') }}">
                @csrf
                <button class="btn-secondaire whitespace-nowrap">Tester</button>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('parametres.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        @foreach($libellesGroupes as $groupe => [$titre, $description])
            @if(isset($groupes[$groupe]))
                <x-carte :titre="$titre" :sous-titre="$description">
                    <div class="space-y-6">
                        @foreach($groupes[$groupe] as $parametre)
                            <div class="grid gap-3 sm:grid-cols-[1fr_1.4fr] sm:items-start">
                                <div>
                                    <label for="param-{{ $parametre->cle }}" class="block text-sm font-medium text-ardoise-800">
                                        {{ $parametre->libelle }}
                                    </label>
                                    @if($parametre->description)
                                        <p class="mt-1 text-xs leading-relaxed text-ardoise-500">{{ $parametre->description }}</p>
                                    @endif
                                    <p class="mt-1 font-mono text-[10px] uppercase tracking-wide text-ardoise-400">{{ $parametre->cle }}</p>
                                </div>

                                <div>
                                    @switch($parametre->type)
                                        @case('boolean')
                                            <label class="inline-flex cursor-pointer items-center gap-3">
                                                <input type="hidden" name="parametres[{{ $parametre->cle }}]" value="0">
                                                <input type="checkbox" name="parametres[{{ $parametre->cle }}]" value="1"
                                                       id="param-{{ $parametre->cle }}"
                                                       @checked($parametre->valeur_claire === '1')
                                                       class="h-5 w-5 rounded border-ardoise-300 text-petro-600 focus:ring-petro-500">
                                                <span class="text-sm text-ardoise-600">Activé</span>
                                            </label>
                                            @break

                                        @case('password')
                                            <div class="flex gap-2">
                                                <input type="password" name="parametres[{{ $parametre->cle }}]"
                                                       id="param-{{ $parametre->cle }}" class="champ font-mono"
                                                       autocomplete="new-password"
                                                       placeholder="{{ $parametre->valeur_claire ? 'Enregistrée : '.$parametre->valeur_masquee : 'Aucune clé enregistrée' }}">
                                                @if($parametre->valeur_claire)
                                                    <button type="submit" formmethod="POST" form="effacer-{{ $parametre->cle }}"
                                                            class="btn-secondaire whitespace-nowrap !text-red-600">Effacer</button>
                                                @endif
                                            </div>
                                            <p class="mt-1 text-xs text-ardoise-500">
                                                Laissez vide pour conserver la clé actuelle.
                                            </p>
                                            @break

                                        @case('select')
                                            <select name="parametres[{{ $parametre->cle }}]" id="param-{{ $parametre->cle }}" class="champ">
                                                @foreach(['log' => 'Journalisation locale (développement)', 'http' => 'Passerelle HTTP externe', 'desactive' => 'Désactivé'] as $valeur => $libelle)
                                                    <option value="{{ $valeur }}" @selected($parametre->valeur_claire === $valeur)>{{ $libelle }}</option>
                                                @endforeach
                                            </select>
                                            @break

                                        @case('textarea')
                                            <textarea name="parametres[{{ $parametre->cle }}]" id="param-{{ $parametre->cle }}"
                                                      rows="3" class="champ">{{ $parametre->valeur_claire }}</textarea>
                                            @break

                                        @case('number')
                                            <input type="number" name="parametres[{{ $parametre->cle }}]" id="param-{{ $parametre->cle }}"
                                                   value="{{ $parametre->valeur_claire }}" min="0" class="champ sm:max-w-[10rem]">
                                            @break

                                        @default
                                            <input type="text" name="parametres[{{ $parametre->cle }}]" id="param-{{ $parametre->cle }}"
                                                   value="{{ $parametre->valeur_claire }}" class="champ">
                                    @endswitch
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-carte>
            @endif
        @endforeach

        <div class="sticky bottom-4 z-10">
            <div class="carte flex flex-wrap items-center justify-between gap-4 p-4">
                <p class="text-sm text-ardoise-600">
                    Les modifications s'appliquent immédiatement à toute la plateforme.
                </p>
                <button class="btn-primaire">Enregistrer les paramètres</button>
            </div>
        </div>
    </form>

    {{-- Formulaires d'effacement des clés (hors du formulaire principal) --}}
    @foreach($groupes as $liste)
        @foreach($liste->where('type', 'password')->where('valeur', '!=', null) as $parametre)
            <form id="effacer-{{ $parametre->cle }}" method="POST"
                  action="{{ route('parametres.cle.effacer', $parametre->cle) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @endforeach

    <x-carte titre="Où obtenir une clé Google Maps ?" class="mt-6">
        <ol class="list-inside list-decimal space-y-2 text-sm leading-relaxed text-ardoise-700">
            <li>Ouvrez la console Google Cloud et sélectionnez (ou créez) un projet dédié à Bénin Pétro.</li>
            <li>Activez les API <strong>Maps JavaScript</strong>, <strong>Geocoding</strong> et <strong>Distance Matrix</strong>.</li>
            <li>Créez une clé API dans « Identifiants », puis restreignez-la au domaine de la plateforme.</li>
            <li>Collez la clé ci-dessus et lancez le test : la plateforme géocode Cotonou pour valider la configuration.</li>
        </ol>
        <p class="mt-4 rounded-lg bg-ardoise-50 px-4 py-3 text-xs leading-relaxed text-ardoise-600">
            Sans clé, la plateforme reste pleinement fonctionnelle : les cartes et l'estimation automatique des distances
            sont simplement masquées, les distances pouvant être saisies manuellement.
        </p>
    </x-carte>
@endsection
