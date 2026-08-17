<?php

namespace App\Http\Controllers;

use App\Models\JournalActivite;
use App\Models\Maintenance;
use App\Models\Vehicule;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:maintenances.consulter')->only(['index', 'show']);
        $this->middleware('permission:maintenances.gerer')->except(['index', 'show']);
    }

    public function index(Request $request)
    {
        $maintenances = Maintenance::with(['vehicule', 'auteur'])
            ->when($request->input('statut'), fn ($q, $s) => $q->where('statut', $s))
            ->when($request->input('vehicule'), fn ($q, $v) => $q->where('vehicule_id', $v))
            ->orderByRaw("FIELD(statut,'en_cours','planifiee','terminee','annulee')")
            ->orderBy('date_prevue')
            ->paginate(15)
            ->withQueryString();

        return view('maintenances.index', [
            'maintenances' => $maintenances,
            'vehicules' => Vehicule::orderBy('immatriculation')->get(),
            'compteurs' => [
                'planifiee' => Maintenance::where('statut', 'planifiee')->count(),
                'en_cours' => Maintenance::where('statut', 'en_cours')->count(),
                'en_retard' => Maintenance::where('statut', 'planifiee')->whereDate('date_prevue', '<', today())->count(),
                'cout_mois' => (float) Maintenance::whereBetween('date_realisee', [now()->startOfMonth(), now()->endOfMonth()])->sum('cout'),
            ],
        ]);
    }

    public function create(Request $request)
    {
        return view('maintenances.create', [
            'vehicules' => Vehicule::orderBy('immatriculation')->get(),
            'vehiculeSelectionne' => $request->input('vehicule'),
        ]);
    }

    public function store(Request $request)
    {
        $donnees = $this->valider($request);
        $donnees['cree_par'] = $request->user()->id;

        $maintenance = Maintenance::create($donnees);

        // Un vehicule pris en atelier n'est plus reservable.
        if ($maintenance->statut === 'en_cours') {
            $maintenance->vehicule->update(['statut' => 'en_maintenance']);
        }

        JournalActivite::enregistrer('maintenance.creee', $maintenance, "Intervention planifiée sur {$maintenance->vehicule->immatriculation}");

        return redirect()->route('maintenances.index')->with('succes', 'Intervention enregistrée.');
    }

    public function edit(Maintenance $maintenance)
    {
        return view('maintenances.edit', [
            'maintenance' => $maintenance,
            'vehicules' => Vehicule::orderBy('immatriculation')->get(),
        ]);
    }

    public function update(Request $request, Maintenance $maintenance)
    {
        $donnees = $this->valider($request);
        $maintenance->update($donnees);

        $vehicule = $maintenance->vehicule;

        if ($maintenance->statut === 'en_cours') {
            $vehicule->update(['statut' => 'en_maintenance']);
        }

        if ($maintenance->statut === 'terminee') {
            $vehicule->update([
                'statut' => $vehicule->statut === 'en_maintenance' ? 'disponible' : $vehicule->statut,
                'kilometrage' => max($vehicule->kilometrage, (int) $maintenance->kilometrage),
            ]);
        }

        JournalActivite::enregistrer('maintenance.modifiee', $maintenance, "Intervention mise à jour sur {$vehicule->immatriculation}");

        return redirect()->route('maintenances.index')->with('succes', 'Intervention mise à jour.');
    }

    public function destroy(Maintenance $maintenance)
    {
        $maintenance->delete();

        return back()->with('succes', 'Intervention supprimée.');
    }

    protected function valider(Request $request): array
    {
        return $request->validate([
            'vehicule_id' => ['required', 'exists:vehicules,id'],
            'type' => ['required', 'in:'.implode(',', array_keys(Maintenance::TYPES))],
            'intitule' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'date_prevue' => ['nullable', 'date'],
            'date_realisee' => ['nullable', 'date'],
            'kilometrage' => ['nullable', 'integer', 'min:0'],
            'cout' => ['nullable', 'numeric', 'min:0'],
            'prestataire' => ['nullable', 'string', 'max:150'],
            'statut' => ['required', 'in:'.implode(',', array_keys(Maintenance::STATUTS))],
        ], [], ['vehicule_id' => 'véhicule', 'intitule' => 'intitulé']);
    }
}
