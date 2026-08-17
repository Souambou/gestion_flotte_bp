<?php

namespace App\Http\Controllers;

use App\Models\JournalActivite;
use App\Models\Litige;
use App\Models\Parametre;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\AlerteFlotte;
use App\Notifications\LitigeDeclareQhse;
use App\Support\Notificateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class LitigeController extends Controller
{
    public function index(Request $request)
    {
        $litiges = Litige::with(['reservation', 'declarant', 'responsable'])
            ->when($request->user()->estCommercial() && ! $request->user()->can('litiges.traiter'),
                fn ($q) => $q->where('declare_par', $request->user()->id))
            ->when($request->input('statut'), fn ($q, $s) => $q->where('statut', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('litiges.index', [
            'litiges' => $litiges,
            'compteurs' => collect(Litige::STATUTS)->mapWithKeys(fn ($l, $c) => [$c => Litige::where('statut', $c)->count()]),
        ]);
    }

    public function create(Request $request)
    {
        return view('litiges.create', [
            'reservations' => Reservation::pourUtilisateur($request->user())
                ->latest('date_debut')
                ->take(50)
                ->get(),
            'reservationSelectionnee' => $request->input('reservation'),
        ]);
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'reservation_id' => ['nullable', 'exists:reservations,id'],
            'type' => ['required', 'in:'.implode(',', array_keys(Litige::TYPES))],
            'objet' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'min:15', 'max:3000'],
            'gravite' => ['required', 'in:faible,moyenne,elevee'],
        ], [], ['objet' => 'objet', 'description' => 'description']);

        $donnees['declare_par'] = $request->user()->id;
        $donnees['vehicule_id'] = $donnees['reservation_id']
            ? Reservation::find($donnees['reservation_id'])?->vehicule_id
            : null;

        $litige = Litige::create($donnees);

        JournalActivite::enregistrer('litige.ouvert', $litige, "Litige {$litige->reference} déclaré");

        Notificateur::envoyerA(
            User::actifs()->whereHas('roles', fn ($q) => $q->whereIn('name', ['administrateur', 'responsable_flotte']))->get(),
            new AlerteFlotte(
                'Nouveau litige déclaré',
                "{$litige->reference} — {$litige->objet}",
                route('litiges.show', $litige),
                $litige->gravite === 'elevee' ? 'danger' : 'attention',
            )
        );

        $this->informerQhse($litige);

        return redirect()->route('litiges.show', $litige)
            ->with('succes', "Litige {$litige->reference} enregistré. Le service QHSE en a été informé.");
    }

    /**
     * Transmission automatique au service QHSE.
     *
     * Deux canaux, complementaires : l'adresse fonctionnelle du service, et
     * les collaborateurs rattaches au QHSE qui disposent d'un compte. Un
     * meme destinataire n'est jamais notifie deux fois.
     */
    protected function informerQhse(Litige $litige): void
    {
        $notification = new LitigeDeclareQhse($litige);

        $adresse = Parametre::valeur('societe_email_qhse', config('beninpetro.societe.email_qhse'));

        if ($adresse) {
            Notification::route('mail', $adresse)->notify($notification);
        }

        $collaborateurs = User::actifs()
            ->where('departement', 'qhse')
            ->when($adresse, fn ($q) => $q->where('email', '!=', $adresse))
            ->get();

        Notificateur::envoyerA($collaborateurs, $notification);
    }

    public function show(Request $request, Litige $litige)
    {
        abort_if(
            $litige->declare_par !== $request->user()->id && ! $request->user()->can('litiges.traiter'),
            403,
            'Ce dossier est suivi par un autre collaborateur.'
        );

        return view('litiges.show', ['litige' => $litige->load(['reservation.demandeur', 'vehicule', 'declarant', 'responsable'])]);
    }

    public function update(Request $request, Litige $litige)
    {
        abort_unless($request->user()->can('litiges.traiter'), 403);

        $donnees = $request->validate([
            'statut' => ['required', 'in:'.implode(',', array_keys(Litige::STATUTS))],
            'resolution' => ['nullable', 'string', 'max:3000'],
        ]);

        if (in_array($donnees['statut'], ['resolu', 'clos']) && empty($donnees['resolution'])) {
            return back()->withErrors(['resolution' => 'Décrivez la résolution avant de clore le dossier.'])->withInput();
        }

        $litige->update(array_merge($donnees, [
            'traite_par' => $request->user()->id,
            'resolu_at' => in_array($donnees['statut'], ['resolu', 'clos']) ? now() : null,
        ]));

        Notificateur::envoyer($litige->declarant, new AlerteFlotte(
            'Mise à jour de votre litige',
            "{$litige->reference} — statut : {$litige->statut_libelle}",
            route('litiges.show', $litige),
        ));

        JournalActivite::enregistrer('litige.maj', $litige, "Litige {$litige->reference} — {$litige->statut_libelle}");

        return back()->with('succes', 'Dossier mis à jour et déclarant notifié.');
    }
}
