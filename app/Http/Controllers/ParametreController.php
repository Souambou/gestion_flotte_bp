<?php

namespace App\Http\Controllers;

use App\Models\JournalActivite;
use App\Models\Parametre;
use App\Services\ServiceGoogleMaps;
use App\Services\ServiceNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Ecran d'administration des parametres : cles API, notifications, regles metier.
 */
class ParametreController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:parametres.gerer');
    }

    public function index()
    {
        return view('parametres.index', [
            'groupes' => Parametre::orderBy('groupe')->orderBy('id')->get()->groupBy('groupe'),
            'mapsActif' => app(ServiceGoogleMaps::class)->estConfigure(),
        ]);
    }

    public function update(Request $request)
    {
        $valeurs = $request->input('parametres', []);

        foreach ($valeurs as $cle => $valeur) {
            $parametre = Parametre::where('cle', $cle)->first();

            if (! $parametre) {
                continue;
            }

            // Un champ secret laisse vide conserve la valeur existante.
            if ($parametre->chiffre && ($valeur === null || $valeur === '')) {
                continue;
            }

            if ($parametre->type === 'boolean') {
                $valeur = $request->boolean("parametres.{$cle}") ? '1' : '0';
            }

            $parametre->valeur = $parametre->chiffre ? Crypt::encryptString((string) $valeur) : $valeur;
            $parametre->save();
        }

        JournalActivite::enregistrer('parametres.modifies', null, 'Paramètres de la plateforme mis à jour');

        return back()->with('succes', 'Paramètres enregistrés.');
    }

    /** Supprime une cle API sans toucher au reste de la configuration. */
    public function effacerCle(string $cle)
    {
        $parametre = Parametre::where('cle', $cle)->firstOrFail();
        $parametre->update(['valeur' => null]);

        return back()->with('succes', "La clé « {$parametre->libelle} » a été effacée.");
    }

    /** Test de connectivite Google Maps depuis l'interface. */
    public function testerMaps(ServiceGoogleMaps $maps)
    {
        if (! $maps->estConfigure()) {
            return back()->with('erreur', 'Renseignez d\'abord la clé Google Maps.');
        }

        $resultat = $maps->geocoder('Cotonou, Bénin');

        return $resultat
            ? back()->with('succes', 'Connexion Google Maps établie (Cotonou localisée).')
            : back()->with('erreur', 'La clé a été refusée par Google. Vérifiez la clé et les API activées (Geocoding, Distance Matrix, Maps JavaScript).');
    }

    /** Test d'envoi SMS vers le numero de l'administrateur connecte. */
    public function testerSms(Request $request, ServiceNotification $sms)
    {
        if (! $request->user()->telephone) {
            return back()->with('erreur', 'Ajoutez un numéro de téléphone à votre profil pour recevoir le SMS de test.');
        }

        $envoye = $sms->envoyerSms($request->user()->telephone, 'Bénin Pétro : test de la passerelle SMS. Configuration opérationnelle.');

        return $envoye
            ? back()->with('succes', 'SMS de test envoyé.')
            : back()->with('erreur', 'Envoi impossible. Vérifiez l\'URL et la clé de la passerelle SMS.');
    }
}
