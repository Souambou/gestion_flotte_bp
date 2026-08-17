@extends('layouts.app')
@section('titre', 'Notifications')
@section('sous-titre', $nonLues ? $nonLues.' notification(s) non lue(s)' : 'Tout est à jour')

@section('contenu')

    @if($nonLues)
        <form method="POST" action="{{ route('notifications.tout-lire') }}" class="mb-5 flex justify-end">
            @csrf
            <button class="btn-secondaire">Tout marquer comme lu</button>
        </form>
    @endif

    <x-carte :padding="false">
        <div class="divide-y divide-ardoise-100">
            @forelse($notifications as $notification)
                @php
                    $donnees = $notification->data;
                    $ton = $donnees['niveau'] ?? 'info';
                @endphp
                <a href="{{ route('notifications.ouvrir', $notification->id) }}"
                   @class([
                       'flex gap-4 p-5 transition hover:bg-ardoise-50',
                       'bg-petro-50/60' => ! $notification->read_at,
                   ])>
                    <span @class([
                        'mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full',
                        'bg-red-500' => $ton === 'danger',
                        'bg-amber-500' => $ton === 'attention',
                        'bg-petro-500' => ! in_array($ton, ['danger', 'attention']),
                        'opacity-30' => $notification->read_at,
                    ])></span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <p @class(['text-sm', 'font-bold text-ardoise-900' => ! $notification->read_at, 'font-medium text-ardoise-700' => $notification->read_at])>
                                {{ $donnees['titre'] ?? 'Notification' }}
                            </p>
                            <span class="text-xs text-ardoise-400">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="mt-1 text-sm text-ardoise-600">{{ $donnees['message'] ?? '' }}</p>
                    </div>
                </a>
            @empty
                <x-vide titre="Aucune notification"
                        message="Les confirmations, rappels de déplacement et alertes de flotte s'afficheront ici."/>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="border-t border-ardoise-100 p-4">{{ $notifications->links() }}</div>
        @endif
    </x-carte>
@endsection
