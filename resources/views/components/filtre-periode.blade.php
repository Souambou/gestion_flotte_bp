@props(['action', 'debut', 'fin'])

<form method="GET" action="{{ $action }}" class="flex flex-wrap items-end gap-3">
    <div>
        <label for="debut" class="mb-1 block text-xs font-medium text-ardoise-600">Du</label>
        <input type="date" name="debut" id="debut" value="{{ request('debut', $debut->format('Y-m-d')) }}" class="champ py-2">
    </div>
    <div>
        <label for="fin" class="mb-1 block text-xs font-medium text-ardoise-600">Au</label>
        <input type="date" name="fin" id="fin" value="{{ request('fin', $fin->format('Y-m-d')) }}" class="champ py-2">
    </div>
    <button class="btn-secondaire">Appliquer</button>
    {{ $slot }}
</form>
