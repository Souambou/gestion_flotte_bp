<?php

namespace App\Http\Controllers;

use App\Models\Chauffeur;
use App\Models\JournalActivite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChauffeurController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:chauffeurs.consulter')->only(['index', 'show']);
        $this->middleware('permission:chauffeurs.creer')->only(['create', 'store']);
        $this->middleware('permission:chauffeurs.modifier')->only(['edit', 'update']);
        $this->middleware('permission:chauffeurs.supprimer')->only('destroy');
    }

    public function index(Request $request)
    {
        $chauffeurs = Chauffeur::query()
            ->recherche($request->input('q'))
            ->when($request->input('statut'), fn ($q, $statut) => $q->where('statut', $statut))
            ->orderBy('nom')
            ->paginate(12)
            ->withQueryString();

        return view('chauffeurs.index', [
            'chauffeurs' => $chauffeurs,
            'compteurs' => [
                'total' => Chauffeur::count(),
                'disponible' => Chauffeur::where('statut', 'disponible')->count(),
                'en_deplacement' => Chauffeur::where('statut', 'en_deplacement')->count(),
                'permis_a_renouveler' => Chauffeur::whereNotNull('date_expiration_permis')
                    ->whereDate('date_expiration_permis', '<=', now()->addDays(config('beninpetro.maintenance.alerte_permis_jours')))
                    ->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('chauffeurs.create', [
            'matriculePrevu' => Chauffeur::genererMatricule(),
        ]);
    }

    public function store(Request $request)
    {
        $donnees = $this->valider($request);

        if ($request->hasFile('photo')) {
            $donnees['photo'] = $request->file('photo')->store('chauffeurs', 'public');
        }

        $chauffeur = Chauffeur::create($donnees);

        JournalActivite::enregistrer('chauffeur.cree', $chauffeur, "Chauffeur {$chauffeur->nom_complet} enregistré");

        return redirect()->route('chauffeurs.show', $chauffeur)
            ->with('succes', "{$chauffeur->nom_complet} fait désormais partie de l'équipe.");
    }

    public function show(Chauffeur $chauffeur)
    {
        return view('chauffeurs.show', [
            'chauffeur' => $chauffeur,
            'deplacements' => $chauffeur->deplacements()->with(['vehicule', 'reservation'])->latest('depart_reel_at')->take(10)->get(),
            'reservations' => $chauffeur->reservations()->with('demandeur')->latest('date_debut')->take(10)->get(),
            'statistiques' => [
                'deplacements_mois' => $chauffeur->deplacements()->whereBetween('depart_reel_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
                'km_mois' => (int) $chauffeur->deplacements()
                    ->whereBetween('depart_reel_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->whereNotNull('km_arrivee')
                    ->selectRaw('COALESCE(SUM(km_arrivee - km_depart),0) as t')->value('t'),
                'deplacements_total' => $chauffeur->deplacements()->count(),
            ],
        ]);
    }

    public function edit(Chauffeur $chauffeur)
    {
        return view('chauffeurs.edit', ['chauffeur' => $chauffeur]);
    }

    public function update(Request $request, Chauffeur $chauffeur)
    {
        $donnees = $this->valider($request, $chauffeur);

        if ($request->hasFile('photo')) {
            if ($chauffeur->photo) {
                Storage::disk('public')->delete($chauffeur->photo);
            }
            $donnees['photo'] = $request->file('photo')->store('chauffeurs', 'public');
        }

        $chauffeur->update($donnees);

        JournalActivite::enregistrer('chauffeur.modifie', $chauffeur, "Fiche de {$chauffeur->nom_complet} mise à jour");

        return redirect()->route('chauffeurs.show', $chauffeur)->with('succes', 'La fiche chauffeur est à jour.');
    }

    public function destroy(Chauffeur $chauffeur)
    {
        if ($chauffeur->reservations()->whereIn('statut', ['validee', 'en_cours'])->exists()) {
            return back()->with('erreur', 'Ce chauffeur est affecté à des déplacements à venir. Réaffectez-les avant de le retirer.');
        }

        $nom = $chauffeur->nom_complet;
        $chauffeur->delete();

        JournalActivite::enregistrer('chauffeur.supprime', null, "Chauffeur {$nom} retiré");

        return redirect()->route('chauffeurs.index')->with('succes', "{$nom} a été retiré de la liste des chauffeurs.");
    }

    protected function valider(Request $request, ?Chauffeur $chauffeur = null): array
    {
        $id = $chauffeur?->id;

        return $request->validate([
            // Le matricule est attribue par la plateforme : il n'est plus saisi.
            'nom' => ['required', 'string', 'max:80'],
            'prenom' => ['required', 'string', 'max:80'],
            'telephone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'numero_permis' => ['required', 'string', 'max:50'],
            'categorie_permis' => ['required', 'string', 'max:20'],
            'date_expiration_permis' => ['nullable', 'date'],
            'date_embauche' => ['nullable', 'date'],
            'statut' => ['required', 'in:'.implode(',', array_keys(Chauffeur::STATUTS))],
            'photo' => ['nullable', 'image', 'max:4096'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
