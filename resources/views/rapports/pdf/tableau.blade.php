<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $titre }}</title>
    <style>
        @page { margin: 24mm 14mm 18mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1f2d29; margin: 0; }
        .entete { background: #01582D; padding: 10px 12px; margin: 0 0 16px 0; }
        .entete table { width: 100%; border-collapse: collapse; }
        .entete td { vertical-align: middle; padding: 0; }
        .entete img { height: 24px; }
        .meta { font-size: 8.5px; color: #9ADB5A; text-align: right; line-height: 1.4; }
        .meta strong { color: #ffffff; font-size: 10px; }
        h1 { font-size: 14px; margin: 0 0 2px; color: #01582D; }
        .periode { font-size: 9px; color: #6b7d76; margin-bottom: 14px; }
        .synthese { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .synthese td { width: 25%; padding: 8px 10px; background: #f2f8f4; border: 1px solid #dbeae1; }
        .synthese .libelle { font-size: 7.5px; text-transform: uppercase; color: #6b7d76; letter-spacing: 0.4px; }
        .synthese .valeur { font-size: 14px; font-weight: bold; color: #01582D; }
        table.donnees { width: 100%; border-collapse: collapse; }
        table.donnees th { background: #01582D; color: #fff; text-align: left; padding: 6px 7px; font-size: 8.5px;
                           text-transform: uppercase; letter-spacing: 0.3px; }
        table.donnees td { padding: 5px 7px; border-bottom: 1px solid #e6ece9; }
        table.donnees tr:nth-child(even) td { background: #f8faf9; }
        .pied { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 8px; color: #93a29c;
                border-top: 1px solid #e6ece9; padding-top: 5px; }
        .pied .droite { float: right; }
        .vide { padding: 24px; text-align: center; color: #93a29c; font-style: italic; }
    </style>
</head>
<body>

<div class="entete">
    <table>
        <tr>
            <td style="width: 45%;">
                @if($logo)
                    <img src="{{ $logo }}" alt="Bénin Pétro">
                @endif
            </td>
            <td class="meta">
                <strong>{{ config('beninpetro.societe.nom') }}</strong><br>
                {{ config('beninpetro.societe.adresse') }}
            </td>
        </tr>
    </table>
</div>

<h1>{{ $titre }}</h1>
<p class="periode">
    Période du {{ $kpi->debut()->format('d/m/Y') }} au {{ $kpi->fin()->format('d/m/Y') }}
    · Document généré automatiquement le {{ $genereLe->format('d/m/Y à H:i') }}
</p>

<table class="synthese">
    <tr>
        <td>
            <div class="libelle">Réservations</div>
            <div class="valeur">{{ $synthese['reservations_total'] }}</div>
        </td>
        <td>
            <div class="libelle">Taux de validation</div>
            <div class="valeur">{{ $synthese['taux_validation'] }} %</div>
        </td>
        <td>
            <div class="libelle">Occupation flotte</div>
            <div class="valeur">{{ $synthese['taux_occupation_flotte'] }} %</div>
        </td>
        <td>
            <div class="libelle">Kilomètres</div>
            <div class="valeur">{{ number_format($synthese['km_parcourus'], 0, ',', ' ') }}</div>
        </td>
    </tr>
</table>

@if(count($lignes))
    <table class="donnees">
        <thead>
        <tr>
            @foreach($entetes as $entete)
                <th>{{ $entete }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach($lignes as $ligne)
            <tr>
                @foreach($ligne as $cellule)
                    <td>{{ $cellule }}</td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <p class="vide">Aucune donnée sur la période sélectionnée.</p>
@endif

<div class="pied">
    {{ config('beninpetro.societe.nom') }} — Plateforme de gestion de flotte
    <span class="droite">Document interne · {{ $genereLe->format('d/m/Y H:i') }}</span>
</div>

</body>
</html>
