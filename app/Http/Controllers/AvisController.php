<?php

namespace App\Http\Controllers;

use App\Models\Avis;
use App\Models\Reservation;
use Illuminate\Http\Request;

class AvisController extends Controller
{
    public function index(Request $request)
    {
        $avis = Avis::with(['reservation.vehicule', 'reservation.chauffeur', 'auteur'])
            ->when($request->input('note'), fn ($q, $n) => $q->where('note', $n))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('avis.index', [
            'avis' => $avis,
            'moyenne' => round((float) Avis::avg('note'), 1),
            'total' => Avis::count(),
            'repartition' => Avis::selectRaw('note, COUNT(*) as total')->groupBy('note')->pluck('total', 'note'),
        ]);
    }

    public function store(Request $request, Reservation $reservation)
    {
        abort_if($reservation->user_id !== $request->user()->id, 403, 'Seul le demandeur peut évaluer ce déplacement.');
        abort_unless($reservation->statut === 'terminee', 403, 'L\'évaluation est ouverte une fois le déplacement terminé.');

        $donnees = $request->validate([
            'note' => ['required', 'integer', 'min:1', 'max:5'],
            'note_vehicule' => ['nullable', 'integer', 'min:1', 'max:5'],
            'note_chauffeur' => ['nullable', 'integer', 'min:1', 'max:5'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ], [], ['note' => 'note globale']);

        Avis::updateOrCreate(
            ['reservation_id' => $reservation->id],
            array_merge($donnees, ['user_id' => $request->user()->id])
        );

        return back()->with('succes', 'Merci, votre évaluation est enregistrée.');
    }
}
