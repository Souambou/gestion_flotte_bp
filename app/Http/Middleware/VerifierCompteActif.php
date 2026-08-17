<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un compte desactive est immediatement deconnecte, meme si la session existe.
 * Un utilisateur dont le mot de passe est provisoire est dirige vers le changement.
 */
class VerifierCompteActif
{
    public function handle(Request $request, Closure $next): Response
    {
        $utilisateur = $request->user();

        if ($utilisateur && ! $utilisateur->actif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('connexion')
                ->withErrors(['email' => "Ce compte a été désactivé. Contactez l'administrateur de la plateforme."]);
        }

        if ($utilisateur && $utilisateur->doit_changer_mot_de_passe
            && ! $request->routeIs('profil.mot-de-passe', 'profil.mot-de-passe.update', 'deconnexion')) {
            return redirect()->route('profil.mot-de-passe');
        }

        return $next($request);
    }
}
