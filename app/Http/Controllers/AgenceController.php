<?php

namespace App\Http\Controllers;

use App\Models\Agence;
use Illuminate\Http\Request;

class AgenceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:agences.gerer')->except('index');
    }

    public function index()
    {
        return view('agences.index', [
            'agences' => Agence::withCount(['vehicules', 'chauffeurs', 'utilisateurs'])->orderBy('nom')->paginate(15),
        ]);
    }

    public function create()
    {
        return view('agences.create');
    }

    public function store(Request $request)
    {
        Agence::create($this->valider($request));

        return redirect()->route('agences.index')->with('succes', 'Site ajouté.');
    }

    public function edit(Agence $agence)
    {
        return view('agences.edit', compact('agence'));
    }

    public function update(Request $request, Agence $agence)
    {
        $agence->update($this->valider($request));

        return redirect()->route('agences.index')->with('succes', 'Site mis à jour.');
    }

    public function destroy(Agence $agence)
    {
        if ($agence->vehicules()->exists() || $agence->chauffeurs()->exists()) {
            return back()->with('erreur', 'Ce site rattache encore des véhicules ou des chauffeurs.');
        }

        $agence->delete();

        return back()->with('succes', 'Site supprimé.');
    }

    protected function valider(Request $request): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:120'],
            'ville' => ['required', 'string', 'max:120'],
            'adresse' => ['nullable', 'string', 'max:250'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'active' => ['required', 'boolean'],
        ]);
    }
}
