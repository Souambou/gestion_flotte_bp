<?php

namespace App\Http\Controllers;

use App\Models\Chauffeur;
use App\Models\Reservation;
use App\Models\Vehicule;
use App\Services\ServiceGoogleMaps;
use App\Support\Departements;
use App\Services\ServiceReservation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    public function __construct(
        protected ServiceReservation $service,
        protected ServiceGoogleMaps $maps,
    ) {
        $this->middleware('permission:reservations.creer')->only(['create', 'store']);
        $this->middleware('permission:reservations.valider')->only(['valider', 'refuser']);
    }

    public function index(Request $request)
    {
        $reservations = Reservation::query()
            ->with(['demandeur', 'vehicule', 'chauffeur'])
            ->pourUtilisateur($request->user())
            ->when($request->input('statut'), fn ($q, $s) => $q->where('statut', $s))
            ->when($request->input('q'), function ($q, $terme) {
                $q->where(function ($sub) use ($terme) {
                    $sub->where('code', 'like', "%{$terme}%")
                        ->orWhere('lieu_depart', 'like', "%{$terme}%")
                        ->orWhere('lieu_arrivee', 'like', "%{$terme}%")
                        ->orWhereHas('demandeur', fn ($u) => $u->where('nom', 'like', "%{$terme}%"));
                });
            })
            ->when($request->input('debut'), fn ($q, $d) => $q->whereDate('date_debut', '>=', $d))
            ->when($request->input('fin'), fn ($q, $f) => $q->whereDate('date_fin', '<=', $f))
            ->duDepartement($request->input('departement'))
            ->triMetier()
            ->paginate(15)
            ->withQueryString();

        $base = Reservation::query()->pourUtilisateur($request->user());

        return view('reservations.index', [
            'reservations' => $reservations,
            'compteurs' => collect(Reservation::STATUTS)->mapWithKeys(fn ($libelle, $cle) => [
                $cle => (clone $base)->where('statut', $cle)->count(),
            ]),
            'departements' => Departements::options(),
        ]);
    }

    public function create(Request $request)
    {
        return view('reservations.create', [
            'typesDeplacement' => config('beninpetro.types_deplacement'),
            'departements' => Departements::options(),
            'departementParDefaut' => $request->user()->departement,
            'mapsActif' => $this->maps->estConfigure(),
        ]);
    }

    public function store(Request $request)
    {
        $donnees = $this->validerDemande($request);

        $reservation = $this->service->creer($donnees, $request->user());

        return redirect()->route('reservations.show', $reservation)
            ->with('succes', "Demande {$reservation->code} envoyée. Le responsable de flotte vous répondra sous peu.");
    }

    public function show(Request $request, Reservation $reservation)
    {
        $utilisateur = $request->user();

        abort_if(
            $utilisateur->estCommercial() && ! $utilisateur->can('reservations.voir_toutes') && $reservation->user_id !== $utilisateur->id,
            403,
            "Cette demande appartient à un autre collaborateur."
        );

        $reservation->load(['demandeur', 'vehicule', 'chauffeur', 'validateur', 'deplacement', 'avis', 'litiges']);

        $alternatives = $reservation->peutEtreValidee() && $utilisateur->can('reservations.valider')
            ? $this->service->alternatives($reservation)
            : ['vehicules' => collect(), 'tous_vehicules' => collect(), 'chauffeurs' => collect()];

        return view('reservations.show', [
            'reservation' => $reservation,
            'alternatives' => $alternatives,
            'cleMaps' => $this->maps->cle(),
        ]);
    }

    public function edit(Request $request, Reservation $reservation)
    {
        $this->autoriserModification($request, $reservation);

        return view('reservations.edit', [
            'reservation' => $reservation,
            'typesDeplacement' => config('beninpetro.types_deplacement'),
            'departements' => Departements::options(),
        ]);
    }

    public function update(Request $request, Reservation $reservation)
    {
        $this->autoriserModification($request, $reservation);

        $donnees = $this->validerDemande($request, $reservation);

        // Une demande deja validee qui change de creneau repasse en validation.
        if ($reservation->statut === 'validee' &&
            ($reservation->date_debut->ne($donnees['date_debut']) || $reservation->date_fin->ne($donnees['date_fin']))) {
            $donnees['statut'] = 'en_attente';
            $donnees['traite_par'] = null;
            $donnees['traite_at'] = null;
        }

        $reservation->update($donnees);

        return redirect()->route('reservations.show', $reservation)
            ->with('succes', 'La demande a été mise à jour.');
    }

    public function valider(Request $request, Reservation $reservation)
    {
        $donnees = $request->validate([
            'vehicule_id' => ['required', 'exists:vehicules,id'],
            'chauffeur_id' => ['nullable', 'exists:chauffeurs,id'],
        ], [], ['vehicule_id' => 'véhicule', 'chauffeur_id' => 'chauffeur']);

        $this->service->valider($reservation, $donnees, $request->user());

        return back()->with('succes', "Demande {$reservation->code} validée. Le commercial a été notifié.");
    }

    public function refuser(Request $request, Reservation $reservation)
    {
        $donnees = $request->validate([
            'motif_refus' => ['required', 'string', 'min:10', 'max:1000'],
            'alternative_proposee' => ['nullable', 'string', 'max:1000'],
        ], [], ['motif_refus' => 'motif du refus', 'alternative_proposee' => 'alternative proposée']);

        $this->service->refuser($reservation, $donnees, $request->user());

        return back()->with('succes', 'Le commercial a été informé du refus et de l\'alternative proposée.');
    }

    public function annuler(Request $request, Reservation $reservation)
    {
        $utilisateur = $request->user();

        abort_if(
            $reservation->user_id !== $utilisateur->id && ! $utilisateur->can('reservations.annuler'),
            403,
            "Vous ne pouvez annuler que vos propres demandes."
        );

        $donnees = $request->validate(['motif_annulation' => ['nullable', 'string', 'max:500']]);

        $this->service->annuler($reservation, $donnees['motif_annulation'] ?? null, $utilisateur);

        return back()->with('succes', "Réservation {$reservation->code} annulée.");
    }

    protected function autoriserModification(Request $request, Reservation $reservation): void
    {
        $utilisateur = $request->user();

        abort_if(
            $reservation->user_id !== $utilisateur->id && ! $utilisateur->can('reservations.modifier'),
            403,
            'Cette demande ne peut pas être modifiée depuis votre compte.'
        );

        abort_unless($reservation->peutEtreModifiee(), 403, 'Une réservation '.strtolower($reservation->statut_libelle).' ne peut plus être modifiée.');
    }

    protected function validerDemande(Request $request, ?Reservation $reservation = null): array
    {
        // Le formulaire saisit la date et l'heure dans deux champs distincts :
        // on les recompose ici en un moment unique avant toute validation.
        // Aucune contrainte sur les minutes : l'heure saisie est prise telle quelle.
        $request->merge([
            'date_debut' => $this->composerMoment($request, 'date_debut'),
            'date_fin' => $this->composerMoment($request, 'date_fin'),
        ]);

        return $request->validate([
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut', 'before:'.$this->limiteDuree($request->input('date_debut'))],
            'lieu_depart' => ['required', 'string', 'max:180'],
            'lieu_arrivee' => ['required', 'string', 'max:180'],
            'type_deplacement' => ['required', Rule::in(array_keys(config('beninpetro.types_deplacement')))],
            'departement' => ['required', Rule::in(Departements::cles())],
            'avec_chauffeur' => ['required', 'boolean'],
            'motif' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'date_fin.after' => 'Le retour doit être postérieur au départ.',
            'date_fin.before' => 'La durée maximale d\'une réservation est de '.config('beninpetro.reservation.duree_max_jours').' jours.',
            'motif.min' => 'Décrivez le motif du déplacement en quelques mots.',
        ], [
            'date_debut' => 'date et heure de départ',
            'date_fin' => 'date et heure de retour',
            'lieu_depart' => 'lieu de départ',
            'lieu_arrivee' => 'destination',
            'type_deplacement' => 'type de déplacement',
            'departement' => 'département',
        ]);
    }

    /**
     * Assemble « jour_x » et « heure_x » en une valeur datetime unique.
     * Retombe sur le champ complet si le formulaire l'envoie deja assemble.
     */
    protected function composerMoment(Request $request, string $champ): ?string
    {
        $jour = $request->input('jour_'.$champ);
        $heure = $request->input('heure_'.$champ);

        if (filled($jour)) {
            return trim($jour.' '.(filled($heure) ? $heure : '00:00'));
        }

        return $request->input($champ);
    }

    /** Date limite de retour, calculee depuis le depart saisi. */
    protected function limiteDuree(?string $depart): string
    {
        try {
            $base = filled($depart) ? \Illuminate\Support\Carbon::parse($depart) : now();
        } catch (\Throwable) {
            $base = now();
        }

        return $base->addDays((int) config('beninpetro.reservation.duree_max_jours'))->toDateTimeString();
    }
}
