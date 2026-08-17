@props(['titre' => 'Rien à afficher', 'message' => null, 'action' => null, 'lien' => null])

<div class="flex flex-col items-center justify-center px-6 py-12 text-center">
    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-petro-50">
        <svg class="h-6 w-6 text-petro-500" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-7-7l7 7-7 7"/>
        </svg>
    </div>
    <p class="font-display text-base font-bold text-ardoise-800">{{ $titre }}</p>
    @if($message)<p class="mt-1 max-w-sm text-sm text-ardoise-500">{{ $message }}</p>@endif
    @if($action && $lien)
        <a href="{{ $lien }}" class="btn-primaire mt-5">{{ $action }}</a>
    @endif
</div>
