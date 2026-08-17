{{--
    Saisie d'un moment en deux champs distincts : la date d'un côté, l'heure de
    l'autre. Plus lisible et plus fiable qu'un champ « datetime-local », dont le
    sélecteur d'heure passe facilement inaperçu.

    Les deux champs sont recombinés côté serveur en une valeur unique ($prefixe).
--}}
@props([
    'prefixe',          // date_debut ou date_fin
    'libelle',
    'heureDefaut' => '08:00',
    'valeur' => null,   // Carbon|null, pour la modification
    'modeleJour' => null,
    'modeleHeure' => null,
])

@php
    $champJour = 'jour_'.$prefixe;
    $champHeure = 'heure_'.$prefixe;
    $jour = old($champJour, $valeur?->format('Y-m-d'));
    $heure = old($champHeure, $valeur?->format('H:i') ?? $heureDefaut);
@endphp

<fieldset>
    <legend class="mb-1.5 block text-sm font-medium text-ardoise-700">
        {{ $libelle }} <span class="text-red-500">*</span>
    </legend>

    <div class="flex gap-2">
        <div class="flex-1">
            <input type="date" name="{{ $champJour }}" id="{{ $champJour }}"
                   value="{{ $jour }}" required
                   aria-label="{{ $libelle }} — date"
                   @if($modeleJour) x-model="{{ $modeleJour }}" @endif
                   {{ $attributes->merge(['class' => 'champ']) }}>
        </div>

        <div class="w-32">
            <input type="time" name="{{ $champHeure }}" id="{{ $champHeure }}"
                   value="{{ $heure }}" required step="60"
                   aria-label="{{ $libelle }} — heure"
                   @if($modeleHeure) x-model="{{ $modeleHeure }}" @endif
                   {{ $attributes->merge(['class' => 'champ']) }}>
        </div>
    </div>

    @error($prefixe)<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
    @error($champJour)<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
    @error($champHeure)<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
</fieldset>
