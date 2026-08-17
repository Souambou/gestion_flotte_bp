@props(['href', 'actif' => false, 'icone' => 'grille'])

@php
    $chemins = [
        'grille' => 'M4 5h6v6H4zM14 5h6v6h-6zM4 15h6v4H4zM14 15h6v4h-6z',
        'calendrier' => 'M8 3v4M16 3v4M4 9h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z',
        'planning' => 'M4 6h16M4 12h10M4 18h7',
        'route' => 'M9 4v16M15 4v16M4 8h5M15 8h5M4 16h5M15 16h5',
        'vehicule' => 'M5 17h14M6 17v2M18 17v2M4 13l1.6-5A2 2 0 017.5 6.6h9A2 2 0 0118.4 8l1.6 5v4H4v-4zM7.5 13h.01M16.5 13h.01',
        'chauffeur' => 'M12 11a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM5 20a7 7 0 0114 0',
        'cle' => 'M14.7 6.3a4 4 0 105 5l-1.5 1.5-2-2-2 2-2-2 2.5-4.5zM3 21l7.5-7.5',
        'alerte' => 'M12 9v4m0 3h.01M10.3 4.9L1.8 19a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 4.9a2 2 0 00-3.4 0z',
        'graphique' => 'M4 20h16M7 16V9M12 16V5M17 16v-4',
        'etoile' => 'M12 4l2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.6-4.8 2.6.9-5.4L4.2 9.7l5.4-.8L12 4z',
        'equipe' => 'M9 11a3 3 0 100-6 3 3 0 000 6zM3 20a6 6 0 0112 0M17 11a3 3 0 100-6M17 14a5 5 0 014 5',
        'bouclier' => 'M12 3l7 3v5c0 4.4-3 8.3-7 10-4-1.7-7-5.6-7-10V6l7-3z',
        'site' => 'M5 21V8l7-4 7 4v13M9 21v-6h6v6',
        'controle' => 'M9 12l2 2 4-4M7.8 4.6h8.4a2 2 0 012 2v12.8a1 1 0 01-1.5.9L12 18l-4.7 2.3a1 1 0 01-1.5-.9V6.6a2 2 0 012-2z',
        'reglage' => 'M12 15a3 3 0 100-6 3 3 0 000 6zM19.4 15a1.6 1.6 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.6 1.6 0 00-1.8-.3 1.6 1.6 0 00-1 1.5V21a2 2 0 11-4 0v-.1A1.6 1.6 0 008 19.4a1.6 1.6 0 00-1.8.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.6 1.6 0 00.3-1.8 1.6 1.6 0 00-1.5-1H2a2 2 0 110-4h.1A1.6 1.6 0 004.6 8a1.6 1.6 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1a1.6 1.6 0 001.8.3H9a1.6 1.6 0 001-1.5V2a2 2 0 114 0v.1a1.6 1.6 0 001 1.5 1.6 1.6 0 001.8-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.6 1.6 0 00-.3 1.8V9a1.6 1.6 0 001.5 1H22a2 2 0 110 4h-.1a1.6 1.6 0 00-1.5 1z',
    ];
@endphp

<a href="{{ $href }}" @class(['lien-nav', 'lien-nav-actif' => $actif])>
    <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $chemins[$icone] ?? $chemins['grille'] }}"/>
    </svg>
    <span class="flex flex-1 items-center gap-2">{{ $slot }}</span>
</a>
