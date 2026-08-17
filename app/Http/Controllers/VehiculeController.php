<?php

namespace App\Http\Controllers;

use App\Models\Agence;
use App\Models\JournalActivite;
use App\Models\Vehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VehiculeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:vehicules.consulter')->only(['index', 'show']);
        $this->middleware('permission:vehicules.creer')->only(['create', 'store']);
        $this->middleware('permission:vehicules.modifier')->only(['edit', 'update', 'changerStatut']);
        $this->middleware('permission:vehicules.supprimer')->only('destroy');
    }

    public function index(Request $request)
    {
        $vehicules = Vehicule::query()
            ->with('agence')
            ->recherche($request->input('q'))
            ->when($request->input('statut'), fn ($q, $statut) => $q->where('statut', $statut))
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->input('agence'), fn ($q, $agence) => $q->where('agence_id', $agence))
            ->orderBy('immatriculation')
            ->paginate(12)
            ->withQueryString();

        return view('vehicules.index', [
            'vehicules' => $vehicules,
            'agences' => Agence::where('active', true)->orderBy('nom')->get(),
            'compteurs' => [
                'total' => Vehicule::count(),
                'disponible' => Vehicule::where('statut', 'disponible')->count(),
                'en_deplacement' => Vehicule::where('statut', 'en_deplacement')->count(),
                'en_maintenance' => Vehicule::where('statut', 'en_maintenance')->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('vehicules.create', ['agences' => Agence::where('active', true)->orderBy('nom')->get()]);
    }

    public function store(Request $request)
    {
        $donnees = $this->valider($request);

        if ($request->hasFile('photo')) {
            $donnees['photo'] = $request->file('photo')->store('vehicules', 'public');
        }

        $vehicule = Vehicule::create($donnees);

        JournalActivite::enregistrer('vehicule.cree', $vehicule, "Véhicule {$vehicule->immatriculation} ajouté à la flotte");

        return redirect()->route('vehicules.show', $vehicule)
            ->with('succes', "Le véhicule {$vehicule->immatriculation} a rejoint la flotte.");
    }

    public function show(Request $request, Vehicule $vehicule)
    {
        $vehicule->load(['agence', 'maintenances' => fn ($q) => $q->latest('date_prevue')->take(10)]);

        return view('vehicules.show', [
            'vehicule' => $vehicule,
            'reservations' => $vehicule->reservations()
                ->with('demandeur')
                ->latest('date_debut')
                ->take(10)
                ->get(),
            'deplacements' => $vehicule->deplacements()
                ->with('chauffeur')
                ->latest('depart_reel_at')
                ->take(10)
                ->get(),
            'checklists' => $vehicule->checklists()->with('auteur')->latest('date_controle')->take(6)->get(),
            'alertes' => $vehicule->alertes(),
            'tauxOccupation' => $vehicule->tauxOccupation(now()->startOfMonth(), now()->endOfMonth()),
        ]);
    }

    public function edit(Vehicule $vehicule)
    {
        return view('vehicules.edit', [
            'vehicule' => $vehicule,
            'agences' => Agence::where('active', true)->orderBy('nom')->get(),
        ]);
    }

    public function update(Request $request, Vehicule $vehicule)
    {
        $donnees = $this->valider($request, $vehicule);

        if ($request->hasFile('photo')) {
            if ($vehicule->photo) {
                Storage::disk('public')->delete($vehicule->photo);
            }
            $donnees['photo'] = $request->file('photo')->store('vehicules', 'public');
        }

        $vehicule->update($donnees);

        JournalActivite::enregistrer('vehicule.modifie', $vehicule, "Véhicule {$vehicule->immatriculation} mis à jour");

        return redirect()->route('vehicules.show', $vehicule)
            ->with('succes', 'Les informations du véhicule sont à jour.');
    }

    /** Changement rapide de disponibilite depuis la fiche vehicule. */
    public function changerStatut(Request $request, Vehicule $vehicule)
    {
        $donnees = $request->validate([
            'statut' => ['required', 'in:disponible,en_deplacement,en_maintenance,hors_service'],
        ]);

        if ($donnees['statut'] !== 'en_deplacement' && $vehicule->reservations()->where('statut', 'en_cours')->exists()) {
            return back()->with('erreur', 'Ce véhicule est engagé sur un déplacement en cours : clôturez le déplacement avant de changer sa disponibilité.');
        }

        $vehicule->update($donnees);

        JournalActivite::enregistrer('vehicule.statut', $vehicule, "Disponibilité passée à {$vehicule->statut_libelle}");

        return back()->with('succes', "Le véhicule est maintenant « {$vehicule->statut_libelle} ».");
    }

    public function destroy(Vehicule $vehicule)
    {
        if ($vehicule->reservations()->whereIn('statut', ['en_attente', 'validee', 'en_cours'])->exists()) {
            return back()->with('erreur', 'Ce véhicule porte des réservations actives. Traitez-les avant de le retirer de la flotte.');
        }

        $immatriculation = $vehicule->immatriculation;
        $vehicule->delete();

        JournalActivite::enregistrer('vehicule.supprime', null, "Véhicule {$immatriculation} retiré de la flotte");

        return redirect()->route('vehicules.index')
            ->with('succes', "Le véhicule {$immatriculation} a été retiré de la flotte.");
    }

    protected function valider(Request $request, ?Vehicule $vehicule = null): array
    {
        return $request->validate([
            'immatriculation' => ['required', 'string', 'max:30', 'unique:vehicules,immatriculation'.($vehicule ? ",{$vehicule->id}" : '')],
            'marque' => ['required', 'string', 'max:80'],
            'modele' => ['required', 'string', 'max:80'],
            'type' => ['required', 'in:'.implode(',', array_keys(Vehicule::TYPES))],
            'annee' => ['nullable', 'integer', 'min:1980', 'max:'.(date('Y') + 1)],
            'carburant' => ['required', 'in:'.implode(',', array_keys(Vehicule::CARBURANTS))],
            'nombre_places' => ['required', 'integer', 'min:1', 'max:60'],
            'kilometrage' => ['required', 'integer', 'min:0'],
            'statut' => ['required', 'in:'.implode(',', array_keys(Vehicule::STATUTS))],
            'agence_id' => ['nullable', 'exists:agences,id'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'date_mise_en_service' => ['nullable', 'date'],
            'date_expiration_assurance' => ['nullable', 'date'],
            'date_visite_technique' => ['nullable', 'date'],
            'date_prochaine_maintenance' => ['nullable', 'date'],
            'km_prochaine_maintenance' => ['nullable', 'integer', 'min:0'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'immatriculation' => 'immatriculation',
            'nombre_places' => 'nombre de places',
        ]);
    }
}
