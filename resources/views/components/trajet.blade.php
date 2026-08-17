@props(['depart', 'arrivee', 'compact' => false])

{{-- Le chevron du logo Bénin Pétro sert de marqueur de direction --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 '.($compact ? 'text-xs' : 'text-sm')]) }}>
    <span class="font-medium text-ardoise-800">{{ $depart }}</span>
    <svg class="h-3 w-4 shrink-0 text-petro-400" viewBox="0 0 16 12" fill="currentColor" aria-hidden="true">
        <path d="M0 4h8V0l8 6-8 6V8H0z"/>
    </svg>
    <span class="font-medium text-ardoise-800">{{ $arrivee }}</span>
</span>
