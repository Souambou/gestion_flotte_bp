<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\JournalActivite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function formulaire()
    {
        return view('auth.connexion');
    }

    public function connexion(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [], ['email' => 'adresse e-mail', 'password' => 'mot de passe']);

        $cle = Str::lower($donnees['email']).'|'.$request->ip();

        // Verrouillage temporaire apres 5 tentatives infructueuses.
        if (RateLimiter::tooManyAttempts($cle, 5)) {
            $secondes = RateLimiter::availableIn($cle);

            throw ValidationException::withMessages([
                'email' => "Trop de tentatives. Réessayez dans {$secondes} secondes ou contactez l'administrateur.",
            ]);
        }

        if (! Auth::attempt($donnees, $request->boolean('remember'))) {
            RateLimiter::hit($cle, 300);

            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects. Vérifiez votre adresse e-mail et votre mot de passe.',
            ]);
        }

        if (! Auth::user()->actif) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => "Ce compte est désactivé. Contactez l'administrateur de la plateforme.",
            ]);
        }

        RateLimiter::clear($cle);
        $request->session()->regenerate();

        Auth::user()->forceFill(['derniere_connexion_at' => now()])->save();
        JournalActivite::enregistrer('auth.connexion', Auth::user(), 'Connexion à la plateforme');

        if (Auth::user()->doit_changer_mot_de_passe) {
            return redirect()->route('profil.mot-de-passe')
                ->with('info', 'Pour votre sécurité, définissez un nouveau mot de passe avant de continuer.');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function deconnexion(Request $request): RedirectResponse
    {
        JournalActivite::enregistrer('auth.deconnexion', Auth::user(), 'Déconnexion');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('connexion');
    }
}
