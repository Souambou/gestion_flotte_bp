@props(['statut' => null, 'ton' => null, 'libelle' => null])

@php
    $tonsParStatut = [
        'en_attente' => 'ambre', 'validee' => 'vert', 'refusee' => 'rouge',
        'en_cours' => 'teal', 'terminee' => 'ardoise', 'annulee' => 'rouge',
        'disponible' => 'vert', 'en_deplacement' => 'teal', 'en_maintenance' => 'ambre',
        'hors_service' => 'rouge', 'indisponible' => 'rouge', 'conge' => 'ardoise',
        'planifiee' => 'ambre', 'incident' => 'rouge',
        'ouvert' => 'ambre', 'en_traitement' => 'teal', 'resolu' => 'vert', 'clos' => 'ardoise',
        'bon' => 'vert', 'moyen' => 'ambre', 'mauvais' => 'rouge',
        'conforme' => 'vert', 'a_surveiller' => 'ambre', 'non_conforme' => 'rouge', 'absent' => 'rouge',
        'faible' => 'ardoise', 'moyenne' => 'ambre', 'elevee' => 'rouge',
    ];

    $classes = [
        'vert' => 'bg-petro-100 text-petro-800',
        'ambre' => 'bg-amber-100 text-amber-800',
        'rouge' => 'bg-red-100 text-red-700',
        'teal' => 'bg-petro-50 text-petro-600 ring-1 ring-petro-200',
        'ardoise' => 'bg-ardoise-100 text-ardoise-700',
    ];

    $tonFinal = $ton ?? ($tonsParStatut[$statut] ?? 'ardoise');
@endphp

<span {{ $attributes->merge(['class' => 'etiquette '.$classes[$tonFinal]]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>
    {{ $libelle ?? $slot }}
</span>
