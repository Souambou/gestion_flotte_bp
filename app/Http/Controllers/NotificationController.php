<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(20),
            'nonLues' => $request->user()->unreadNotifications()->count(),
        ]);
    }
    

    /** Marque une notification comme lue puis redirige vers l'element concerne. */
    public function ouvrir(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect($notification->data['lien'] ?? route('notifications.index'));
    }

    public function toutMarquerLu(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('succes', 'Toutes vos notifications sont marquées comme lues.');
    }

    /** Compteur consomme par la cloche de notifications (polling leger). */
    public function compteur(Request $request)
    {
        return response()->json([
            'non_lues' => $request->user()->unreadNotifications()->count(),
            'dernieres' => $request->user()->unreadNotifications()->take(5)->get()->map(fn ($n) => [
                'id' => $n->id,
                'titre' => $n->data['titre'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'lien' => route('notifications.ouvrir', $n->id),
                'date' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }
}
