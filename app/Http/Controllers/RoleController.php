<?php

namespace App\Http\Controllers;

use App\Models\JournalActivite;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Gestion fine des roles et permissions (Spatie laravel-permission).
 */
class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:roles.gerer');
    }

    public function index()
    {
        return view('roles.index', [
            'roles' => Role::withCount(['permissions', 'users'])->orderBy('name')->get(),
            'permissions' => $this->permissionsGroupees(),
        ]);
    }

    public function create()
    {
        return view('roles.create', ['permissions' => $this->permissionsGroupees()]);
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'name' => ['required', 'string', 'max:60', 'unique:roles,name', 'regex:/^[a-z_]+$/'],
            'permissions' => ['array'],
            'perdeplacements.*' => ['exists:permissions,name'],
        ], [
            'name.regex' => 'Utilisez uniquement des minuscules et des tirets bas (ex. responsable_flotte).',
        ], ['name' => 'nom du rôle']);

        $role = Role::create(['name' => $donnees['name'], 'guard_name' => 'web']);
        $role->syncPermissions($donnees['permissions'] ?? []);

        JournalActivite::enregistrer('role.cree', null, "Rôle {$role->name} créé");

        return redirect()->route('roles.index')->with('succes', "Rôle « {$role->name} » créé.");
    }

    public function edit(Role $role)
    {
        return view('roles.edit', [
            'role' => $role->load('permissions'),
            'permissions' => $this->permissionsGroupees(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $donnees = $request->validate([
            'permissions' => ['array'],
            'perdeplacements.*' => ['exists:permissions,name'],
        ]);

        // Le role administrateur conserve toujours l'ensemble des perdeplacements.
        if ($role->name === 'administrateur') {
            $role->syncPermissions(Permission::all());

            return back()->with('info', 'Le rôle administrateur conserve toutes les permissions par conception.');
        }

        $role->syncPermissions($donnees['permissions'] ?? []);

        JournalActivite::enregistrer('role.modifie', null, "Permissions du rôle {$role->name} mises à jour");

        return redirect()->route('roles.index')->with('succes', "Permissions du rôle « {$role->name} » mises à jour.");
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, array_keys(config('beninpetro.roles')))) {
            return back()->with('erreur', 'Les rôles métier de la plateforme ne peuvent pas être supprimés.');
        }

        if ($role->users()->exists()) {
            return back()->with('erreur', 'Ce rôle est encore attribué à des collaborateurs.');
        }

        $nom = $role->name;
        $role->delete();

        return back()->with('succes', "Rôle « {$nom} » supprimé.");
    }

    /** Regroupe les permissions par module pour l'affichage. */
    protected function permissionsGroupees()
    {
        return Permission::orderBy('name')->get()->groupBy(fn ($p) => explode('.', $p->name)[0]);
    }
}
