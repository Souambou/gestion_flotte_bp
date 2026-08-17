@php
    $selectionnees = $selectionnees ?? collect();
    $libellesModules = [
        'reservations' => 'Réservations',
        'vehicules' => 'Véhicules',
        'chauffeurs' => 'Chauffeurs',
        'deplacements' => 'Déplacements',
        'checklists' => 'Checklists',
        'maintenances' => 'Maintenance',
        'litiges' => 'Litiges',
        'avis' => 'Avis',
        'agences' => 'Sites et agences',
        'rapports' => 'Rapports',
        'utilisateurs' => 'Utilisateurs',
        'roles' => 'Rôles et permissions',
        'parametres' => 'Paramètres',
    ];
    $libellesActions = [
        'consulter' => 'Consulter', 'creer' => 'Créer', 'modifier' => 'Modifier', 'supprimer' => 'Supprimer',
        'valider' => 'Valider et refuser', 'annuler' => 'Annuler', 'gerer' => 'Gérer', 'traiter' => 'Traiter',
        'remplir' => 'Remplir', 'exporter' => 'Exporter', 'consulter_tout' => 'Consulter toutes les données',
    ];
@endphp

<div class="space-y-5" x-data="{ tout: false }">
    <div class="flex items-center justify-between">
        <p class="text-sm text-ardoise-600">{{ $permissions->flatten()->count() }} permissions disponibles</p>
        <button type="button"
                @click="tout = !tout; $root.querySelectorAll('input[name=\'permissions[]\']').forEach(c => c.checked = tout)"
                class="text-xs font-medium text-petro-700 hover:underline">
            Tout cocher / tout décocher
        </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach($permissions as $module => $listeModule)
            <div class="rounded-xl2 border border-ardoise-200 p-4">
                <p class="mb-3 text-sm font-bold text-ardoise-800">{{ $libellesModules[$module] ?? ucfirst($module) }}</p>
                <div class="space-y-2">
                    @foreach($listeModule as $permission)
                        @php $action = explode('.', $permission->name)[1] ?? $permission->name; @endphp
                        <label class="flex cursor-pointer items-start gap-2.5">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                   @checked($selectionnees->contains($permission->name))
                                   class="mt-0.5 h-4 w-4 rounded border-ardoise-300 text-petro-600 focus:ring-petro-500">
                            <span>
                                <span class="block text-sm text-ardoise-700">{{ $libellesActions[$action] ?? ucfirst($action) }}</span>
                                <span class="block font-mono text-[10px] text-ardoise-400">{{ $permission->name }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
