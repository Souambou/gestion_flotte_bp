@props(['nom', 'libelle', 'type' => 'text', 'valeur' => null, 'obligatoire' => false, 'aide' => null, 'placeholder' => null])

<div>
    <label for="{{ $nom }}" class="mb-1.5 block text-sm font-medium text-ardoise-700">
        {{ $libelle }}
        @if($obligatoire)<span class="text-red-500">*</span>@endif
    </label>

    <input type="{{ $type }}"
           name="{{ $nom }}"
           id="{{ $nom }}"
           value="{{ old(str_replace(['[', ']'], ['.', ''], $nom), $valeur) }}"
           @if($obligatoire) required @endif
           @if($placeholder) placeholder="{{ $placeholder }}" @endif
           {{ $attributes->merge(['class' => 'champ']) }}>

    @if($aide)
        <p class="mt-1 text-xs text-ardoise-500">{{ $aide }}</p>
    @endif

    @error(str_replace(['[', ']'], ['.', ''], $nom))
        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
    @enderror
</div>
