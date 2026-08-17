@props(['titre' => null, 'sousTitre' => null, 'action' => null, 'padding' => true])

<section {{ $attributes->merge(['class' => 'carte overflow-hidden']) }}>
    @if($titre)
        <div class="flex items-start justify-between gap-4 border-b border-ardoise-100 px-5 py-4">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wide text-ardoise-700">{{ $titre }}</h2>
                @if($sousTitre)
                    <p class="mt-0.5 text-xs text-ardoise-500">{{ $sousTitre }}</p>
                @endif
            </div>
            @if($action)
                <div class="shrink-0">{{ $action }}</div>
            @endif
        </div>
    @endif

    <div @class(['p-5' => $padding])>
        {{ $slot }}
    </div>
</section>
