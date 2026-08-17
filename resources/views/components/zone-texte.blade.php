@props(['nom', 'libelle', 'valeur' => null, 'obligatoire' => false, 'lignes' => 4, 'aide' => null, 'placeholder' => null])

<div>
    <label for="{{ $nom }}" class="mb-1.5 block text-sm font-medium text-ardoise-700">
        {{ $libelle }}
        @if($obligatoire)<span class="text-red-500">*</span>@endif
    </label>

    <textarea name="{{ $nom }}" id="{{ $nom }}" rows="{{ $lignes }}"
              @if($obligatoire) required @endif
              @if($placeholder) placeholder="{{ $placeholder }}" @endif
              {{ $attributes->merge(['class' => 'champ']) }}>{{ old($nom, $valeur) }}</textarea>

    @if($aide)<p class="mt-1 text-xs text-ardoise-500">{{ $aide }}</p>@endif

    @error($nom)<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
</div>
