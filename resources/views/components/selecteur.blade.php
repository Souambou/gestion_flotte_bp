@props(['nom', 'libelle', 'options' => [], 'valeur' => null, 'obligatoire' => false, 'vide' => null, 'aide' => null])

<div>
    <label for="{{ $nom }}" class="mb-1.5 block text-sm font-medium text-ardoise-700">
        {{ $libelle }}
        @if($obligatoire)<span class="text-red-500">*</span>@endif
    </label>

    <select name="{{ $nom }}" id="{{ $nom }}" @if($obligatoire) required @endif
            {{ $attributes->merge(['class' => 'champ']) }}>
        @if($vide !== null)
            <option value="">{{ $vide }}</option>
        @endif
        @foreach($options as $cle => $libelleOption)
            <option value="{{ $cle }}" @selected((string) old($nom, $valeur) === (string) $cle)>{{ $libelleOption }}</option>
        @endforeach
    </select>

    @if($aide)<p class="mt-1 text-xs text-ardoise-500">{{ $aide }}</p>@endif

    @error($nom)<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
</div>
