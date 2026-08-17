@extends('layouts.app')
@section('titre', 'Kilométrage et consommation')
@section('sous-titre', 'Justificatif de consommation, véhicule par véhicule')

@section('contenu')
    @include('rapports._entete', ['rapportExport' => 'kilometrage'])

    {{-- ------------------------------------------- Kilométrage de toute la flotte --}}
    <x-carte titre="Kilométrage parcouru par véhicule" :padding="false" class="mb-6">
        <div class="overflow-x-auto">
            <table class="tableau">
                <thead>
                <tr>
                    <th>Véhicule</th>
                    <th class="text-right">Déplacements</th>
                    <th class="text-right">Kilomètres parcourus</th>
                    <th class="text-right">Coût carburant</th>
                    <th class="text-right">Coût au kilomètre</th>
                    <th class="text-right">Détail</th>
                </tr>
                </thead>
                <tbody>
                @foreach($performances as $ligne)
                    <tr @class(['bg-petro-50/60' => $vehicule && $ligne['vehicule']->id === $vehicule->id])>
                        <td>
                            <span class="font-mono text-xs font-bold">{{ $ligne['vehicule']->immatriculation }}</span>
                            <p class="text-[11px] text-ardoise-500">{{ $ligne['vehicule']->marque }} {{ $ligne['vehicule']->modele }}</p>
                        </td>
                        <td class="text-right">{{ $ligne['deplacements'] }}</td>
                        <td class="text-right font-semibold">{{ number_format($ligne['kilometres'], 0, ',', ' ') }} km</td>
                        <td class="text-right">@fcfa($ligne['cout'])</td>
                        <td class="text-right text-ardoise-600">
                            {{ $ligne['kilometres'] > 0 ? number_format($ligne['cout'] / $ligne['kilometres'], 0, ',', ' ').' FCFA' : '—' }}
                        </td>
                        <td class="text-right">
                            <a href="{{ route('rapports.kilometrage', ['vehicule' => $ligne['vehicule']->id, 'debut' => $kpi->debut()->format('Y-m-d'), 'fin' => $kpi->fin()->format('Y-m-d')]) }}"
                               class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-ardoise-500 transition hover:bg-petro-50 hover:text-petro-700"
                               title="Voir le justificatif de {{ $ligne['vehicule']->immatriculation }}"
                               aria-label="Voir le justificatif de {{ $ligne['vehicule']->immatriculation }}">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M2.25 12S5.75 5.25 12 5.25 21.75 12 21.75 12 18.25 18.75 12 18.75 2.25 12 2.25 12z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr class="border-t-2 border-ardoise-300 bg-ardoise-50 font-bold">
                    <td class="py-3">TOTAL FLOTTE</td>
                    <td class="py-3 text-right">{{ $performances->sum('deplacements') }}</td>
                    <td class="py-3 text-right">{{ number_format($performances->sum('kilometres'), 0, ',', ' ') }} km</td>
                    <td class="py-3 text-right">@fcfa($performances->sum('cout'))</td>
                    <td class="py-3 text-right"></td>
                    <td class="py-3"></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </x-carte>

    {{-- ------------------------------------------- Justificatif détaillé --}}
    @if($vehicule)
        @php
            $kmTotal = $lignes->sum('km_parcouru');
            $litres = $lignes->sum('debit_litres');
            $coutTotal = $lignes->sum('cout');
        @endphp

        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="font-display text-lg font-bold text-ardoise-900">
                    Justificatif de consommation — {{ $vehicule->immatriculation }}
                </h2>
                <p class="text-sm text-ardoise-500">{{ $vehicule->marque }} {{ $vehicule->modele }}</p>
            </div>

            <select class="champ sm:max-w-[16rem]"
                    onchange="window.location = '{{ route('rapports.kilometrage', ['debut' => $kpi->debut()->format('Y-m-d'), 'fin' => $kpi->fin()->format('Y-m-d')]) }}&vehicule=' + this.value"
                    aria-label="Choisir un véhicule">
                @foreach($vehicules as $v)
                    <option value="{{ $v->id }}" @selected($v->id === $vehicule->id)>
                        {{ $v->immatriculation }} — {{ $v->marque }} {{ $v->modele }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-statistique libelle="Déplacements" :valeur="$lignes->count()"/>
            <x-statistique libelle="Kilomètres parcourus" :valeur="number_format($kmTotal, 0, ',', ' ')" unite="km" ton="teal"/>
            <x-statistique libelle="Carburant consommé" :valeur="number_format($litres, 2, ',', ' ')" unite="L" ton="ambre"/>
            <x-statistique libelle="Consommation moyenne"
                           :valeur="$kmTotal > 0 ? number_format($litres / $kmTotal * 100, 2, ',', ' ') : '—'"
                           unite="L/100 km" ton="vert"/>
        </div>

        <x-carte :padding="false">
            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                    <tr>
                        <th>Index</th>
                        <th>Date</th>
                        <th>Motif du déplacement</th>
                        <th class="text-right">Km au début</th>
                        <th class="text-right">Km à la fin</th>
                        <th class="text-right">Km parcouru</th>
                        <th class="text-right">Conso. aux 100 km</th>
                        <th class="text-right">Débit (L)</th>
                        <th class="text-right">Solde (L)</th>
                        <th class="text-right">Coût</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($lignes as $ligne)
                        <tr>
                            <td class="font-mono text-xs">{{ $ligne['index'] }}</td>
                            <td class="whitespace-nowrap text-ardoise-600">{{ $ligne['date']?->format('d/m/Y') }}</td>
                            <td>
                                <p class="max-w-[22rem] truncate text-sm" title="{{ $ligne['motif'] }}">{{ $ligne['motif'] }}</p>
                                <p class="text-[11px] text-ardoise-500">{{ $ligne['trajet'] }}</p>
                            </td>
                            <td class="text-right">{{ number_format($ligne['km_debut'], 0, ',', ' ') }}</td>
                            <td class="text-right">{{ $ligne['km_fin'] !== null ? number_format($ligne['km_fin'], 0, ',', ' ') : '—' }}</td>
                            <td class="text-right font-semibold">{{ $ligne['km_parcouru'] !== null ? number_format($ligne['km_parcouru'], 0, ',', ' ') : '—' }}</td>
                            <td class="text-right">{{ $ligne['consommation_100km'] !== null ? number_format($ligne['consommation_100km'], 2, ',', ' ') : '—' }}</td>
                            <td class="text-right text-amber-700">{{ $ligne['debit_litres'] !== null ? number_format($ligne['debit_litres'], 2, ',', ' ') : '—' }}</td>
                            <td class="text-right font-medium">{{ number_format($ligne['solde_litres'], 2, ',', ' ') }}</td>
                            <td class="whitespace-nowrap text-right">@fcfa($ligne['cout'])</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <x-vide titre="Aucun déplacement sur la période"
                                        message="Ce véhicule n'a pas circulé sur la plage de dates sélectionnée."/>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                    @if($lignes->isNotEmpty())
                        <tfoot>
                        <tr class="border-t-2 border-ardoise-300 bg-ardoise-50 font-bold">
                            <td colspan="5" class="py-3 text-right">TOTAL</td>
                            <td class="py-3 text-right">{{ number_format($kmTotal, 0, ',', ' ') }}</td>
                            <td class="py-3 text-right">
                                {{ $kmTotal > 0 ? number_format($litres / $kmTotal * 100, 2, ',', ' ') : '—' }}
                            </td>
                            <td class="py-3 text-right">{{ number_format($litres, 2, ',', ' ') }}</td>
                            <td class="py-3 text-right">{{ number_format($lignes->last()['solde_litres'], 2, ',', ' ') }}</td>
                            <td class="py-3 text-right">@fcfa($coutTotal)</td>
                        </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-carte>

        <p class="mt-4 text-xs leading-relaxed text-ardoise-500">
            Les relevés proviennent des kilométrages saisis à l'ouverture et à la clôture de chaque déplacement.
            La consommation aux 100 km est calculée à partir du carburant déclaré à la clôture.
        </p>
    @else
        <x-vide titre="Aucun véhicule dans la flotte"
                message="Ajoutez un véhicule pour suivre sa consommation."/>
    @endif
@endsection
