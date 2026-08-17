<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfilController extends Controller
{
    public function edit(Request $request)
    {
        return view('profil.edit', ['utilisateur' => $request->user()->load('roles')]);
    }

    public function update(Request $request)
    {
        $utilisateur = $request->user();

        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:80'],
            'prenom' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:150', "unique:users,email,{$utilisateur->id}"],
            'telephone' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            if ($utilisateur->photo) {
                Storage::disk('public')->delete($utilisateur->photo);
            }
            $donnees['photo'] = $request->file('photo')->store('utilisateurs', 'public');
        }

        $utilisateur->update($donnees);

        return back()->with('succes', 'Profil mis à jour.');
    }

    public function formulaireMotDePasse()
    {
        return view('profil.mot-de-passe');
    }

    public function changerMotDePasse(Request $request)
    {
        $donnees = $request->validate([
            'mot_de_passe_actuel' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'mot_de_passe_actuel.current_password' => 'Le mot de passe actuel est incorrect.',
        ], [
            'mot_de_passe_actuel' => 'mot de passe actuel',
            'password' => 'nouveau mot de passe',
        ]);

        $request->user()->update([
            'password' => Hash::make($donnees['password']),
            'doit_changer_mot_de_passe' => false,
        ]);

        return redirect()->route('dashboard')->with('succes', 'Nouveau mot de passe enregistré.');
    }
}
