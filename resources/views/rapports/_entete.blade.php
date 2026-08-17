@php
    $onglets = [
        'rapports.index' => 'Synthèse',
        'rapports.historique' => 'Historique',
        'rapports.departements' => 'Par service',
        'rapports.kilometrage' => 'Kilométrage',
        'rapports.occupation' => 'Occupation',
        'rapports.checklists' => 'Checklists',
        'rapports.couts' => 'Coûts',
    ];
    $parametresPeriode = ['debut' => $kpi->debut()->format('Y-m-d'), 'fin' => $kpi->fin()->format('Y-m-d')];

    // Le justificatif porte sur un vehicule precis : on le conserve dans les liens.
    $parametresExport = $parametresPeriode;
    if (request()->filled('vehicule')) {
        $parametresExport['vehicule'] = request('vehicule');
    }
@endphp

<div class="mb-6 space-y-4 sans-impression">
    <div class="flex flex-wrap gap-2">
        @foreach($onglets as $route => $libelle)
            <a href="{{ route($route, $parametresPeriode) }}"
               @class(['btn-secondaire', '!bg-petro-700 !text-white !border-petro-700' => request()->routeIs($route)])>
                {{ $libelle }}
            </a>
        @endforeach
    </div>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <x-filtre-periode :action="route(request()->route()->getName())" :debut="$kpi->debut()" :fin="$kpi->fin()"/>

        @can('rapports.exporter')
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-medium text-ardoise-500">Exporter :</span>
                @foreach(['pdf' => 'PDF', 'xlsx' => 'Excel', 'csv' => 'CSV'] as $format => $libelleFormat)
                    <a href="{{ route('rapports.export', array_merge(['rapport' => $rapportExport ?? 'synthese', 'format' => $format], $parametresExport)) }}"
                       class="btn-secondaire !py-1.5 text-xs">{{ $libelleFormat }}</a>
                @endforeach
            </div>
        @endcan
    </div>

    <p class="text-xs text-ardoise-500">
        Période analysée : du {{ $kpi->debut()->format('d/m/Y') }} au {{ $kpi->fin()->format('d/m/Y') }}.
        Les indicateurs sont recalculés par le serveur à chaque consultation.
    </p>
</div>
