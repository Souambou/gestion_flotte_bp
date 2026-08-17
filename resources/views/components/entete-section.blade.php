@props(['titre', 'description' => null])

<div class="mb-4 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="font-display text-lg font-bold text-ardoise-900">{{ $titre }}</h2>
        @if($description)<p class="text-sm text-ardoise-500">{{ $description }}</p>@endif
    </div>
    <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
</div>
