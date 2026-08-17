<?php

namespace App\Http\Controllers;

use App\Models\Agence;
use App\Models\JournalActivite;
use App\Models\User;
use App\Notifications\AlerteFlotte;
use App\Support\Departements;
use App\Support\Notificateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UtilisateurController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:utilisateurs.consulter')->only(['index', 'show']);
        $this->middleware('permission:utilisateurs.gerer')->except(['index', 'show']);
    }

    public function index(Request $request)
    {
        $utilisateurs = User::with('roles')
            ->recherche($request->input('q'))
            ->when($request->input('role'), fn ($q, $role) => $q->whereHas('roles', fn ($r) => $r->where('name', $role)))
            ->when($request->filled('actif'), fn ($q) => $q->where('actif', $request->boolean('actif')))
            ->orderBy('nom')
            ->paginate(15)
            ->withQueryString();

        return view('utilisateurs.index', [
            'utilisateurs' => $utilisateurs,
            'roles' => Role::orderBy('name')->get(),
            'compteurs' => [
                'total' => User::count(),
                'actifs' => User::where('actif', true)->count(),
                'inactifs' => User::where('actif', false)->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('utilisateurs.create', [
            'roles' => Role::orderBy('name')->get(),
            'departements' => Departements::options(),
            'agences' => Agence::orderBy('nom')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'matricule' => ['nullable', 'string', 'max:30', 'unique:users,matricule'],
            'nom' => ['required', 'string', 'max:80'],
            'prenom' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'poste' => ['nullable', 'string', 'max:100'],
            'departement' => ['nullable', Rule::in(Departements::cles())],
            'agence_id' => ['nullable', 'exists:agences,id'],
            'role' => ['required', 'exists:roles,name'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'actif' => ['required', 'boolean'],
        ], [], ['role' => 'rôle', 'password' => 'mot de passe', 'agence_id' => 'site']);

        // Un mot de passe provisoire est genere si l'administrateur n'en fournit pas.
        $motDePasseProvisoire = $donnees['password'] ?? Str::password(12);

        $utilisateur = User::create(array_merge(
            collect($donnees)->except(['role', 'password'])->all(),
            [
                'password' => Hash::make($motDePasseProvisoire),
                'doit_changer_mot_de_passe' => empty($donnees['password']),
            ]
        ));

        $utilisateur->syncRoles([$donnees['role']]);

        JournalActivite::enregistrer('utilisateur.cree', $utilisateur, "Compte {$utilisateur->email} créé ({$donnees['role']})");

        return redirect()->route('utilisateurs.index')
            ->with('succes', "Compte créé pour {$utilisateur->nom_complet}.")
            ->with('mot_de_passe_provisoire', empty($donnees['password']) ? $motDePasseProvisoire : null);
    }

    public function show(User $utilisateur)
    {
        return view('utilisateurs.show', [
            'utilisateur' => $utilisateur->load('roles'),
            'reservations' => $utilisateur->reservations()->with('vehicule')->latest('date_debut')->take(10)->get(),
            'activites' => $utilisateur->activites()->latest()->take(20)->get(),
        ]);
    }

    public function edit(User $utilisateur)
    {
        return view('utilisateurs.edit', [
            'utilisateur' => $utilisateur->load('roles'),
            'roles' => Role::orderBy('name')->get(),
            'departements' => Departements::options(),
            'agences' => Agence::orderBy('nom')->get(),
        ]);
    }

    public function update(Request $request, User $utilisateur)
    {
        $donnees = $request->validate([
            'matricule' => ['nullable', 'string', 'max:30', "unique:users,matricule,{$utilisateur->id}"],
            'nom' => ['required', 'string', 'max:80'],
            'prenom' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:150', "unique:users,email,{$utilisateur->id}"],
            'telephone' => ['nullable', 'string', 'max:30'],
            'poste' => ['nullable', 'string', 'max:100'],
            'departement' => ['nullable', Rule::in(Departements::cles())],
            'agence_id' => ['nullable', 'exists:agences,id'],
            'role' => ['required', 'exists:roles,name'],
            'actif' => ['required', 'boolean'],
        ], [], ['role' => 'rôle', 'agence_id' => 'site']);

        // Un administrateur ne peut pas se retirer lui-meme ses propres droits.
        if ($utilisateur->id === $request->user()->id && $donnees['role'] !== 'administrateur' && $utilisateur->estAdministrateur()) {
            return back()->with('erreur', 'Vous ne pouvez pas modifier votre propre rôle administrateur.');
        }

        $utilisateur->update(collect($donnees)->except('role')->all());
        $utilisateur->syncRoles([$donnees['role']]);

        JournalActivite::enregistrer('utilisateur.modifie', $utilisateur, "Compte {$utilisateur->email} mis à jour");

        return redirect()->route('utilisateurs.index')->with('succes', 'Compte mis à jour.');
    }

    /** Activation / desactivation rapide d'un compte. */
    public function basculerActivation(Request $request, User $utilisateur)
    {
        if ($utilisateur->id === $request->user()->id) {
            return back()->with('erreur', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $utilisateur->update(['actif' => ! $utilisateur->actif]);

        JournalActivite::enregistrer('utilisateur.activation', $utilisateur, $utilisateur->actif ? 'Compte réactivé' : 'Compte désactivé');

        return back()->with('succes', $utilisateur->actif
            ? "Le compte de {$utilisateur->nom_complet} est réactivé."
            : "Le compte de {$utilisateur->nom_complet} est désactivé.");
    }

    /** Reinitialisation du mot de passe par l'administrateur. */
    public function reinitialiserMotDePasse(User $utilisateur)
    {
        $provisoire = Str::password(12);

        $utilisateur->update([
            'password' => Hash::make($provisoire),
            'doit_changer_mot_de_passe' => true,
        ]);

        Notificateur::envoyer($utilisateur, new AlerteFlotte(
            'Mot de passe réinitialisé',
            'Votre mot de passe a été réinitialisé par un administrateur. Connectez-vous avec le mot de passe provisoire qui vous a été communiqué, puis définissez-en un nouveau.',
            route('connexion'),
        ));

        JournalActivite::enregistrer('utilisateur.mdp_reinitialise', $utilisateur, "Mot de passe réinitialisé pour {$utilisateur->email}");

        return back()
            ->with('succes', "Mot de passe réinitialisé pour {$utilisateur->nom_complet}.")
            ->with('mot_de_passe_provisoire', $provisoire);
    }

    public function destroy(Request $request, User $utilisateur)
    {
        if ($utilisateur->id === $request->user()->id) {
            return back()->with('erreur', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        if ($utilisateur->reservations()->whereIn('statut', ['en_attente', 'validee', 'en_cours'])->exists()) {
            return back()->with('erreur', 'Ce collaborateur a des réservations actives. Désactivez plutôt son compte.');
        }

        $nom = $utilisateur->nom_complet;
        $utilisateur->delete();

        JournalActivite::enregistrer('utilisateur.supprime', null, "Compte de {$nom} supprimé");

        return redirect()->route('utilisateurs.index')->with('succes', "Le compte de {$nom} a été supprimé.");
    }
}
