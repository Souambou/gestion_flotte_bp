@props(['libelle', 'valeur', 'unite' => null, 'variation' => null, 'ton' => 'neutre', 'href' => null])

@php
    $tons = [
        'neutre' => 'text-ardoise-900',
        'vert' => 'text-petro-600',
        'ambre' => 'text-amber-600',
        'rouge' => 'text-red-600',
        'teal' => 'text-petro-500',
    ];
    $balise = $href ? 'a' : 'div';
@endphp

<{{ $balise }} @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'carte block p-5 transition hover:border-petro-300']) }}>
    <p class="text-xs font-semibold uppercase tracking-wider text-ardoise-500">{{ $libelle }}</p>
    <p class="mt-2 flex items-baseline gap-1">
        <span class="font-display text-3xl font-extrabold {{ $tons[$ton] ?? $tons['neutre'] }}">{{ $valeur }}</span>
        @if($unite)<span class="text-sm font-medium text-ardoise-400">{{ $unite }}</span>@endif
    </p>
    @if($variation)
        <p class="mt-1 text-xs text-ardoise-500">{{ $variation }}</p>
    @endif
</{{ $balise }}>
