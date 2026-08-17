@extends('layouts.app')
@section('titre', 'Bonjour '.auth()->user()->prenom)
@section('sous-titre', 'Vos demandes de véhicule et leur avancement')

@section('contenu')

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-statistique libelle="En attente" :valeur="$statistiques['en_attente']" ton="ambre"
                       variation="Validation par le responsable"
                       :href="route('reservations.index', ['statut' => 'en_attente'])"/>
        <x-statistique libelle="Confirmées" :valeur="$statistiques['validees']" ton="vert"
                       variation="Départs à venir"
                       :href="route('reservations.index', ['statut' => 'validee'])"/>
        <x-statistique libelle="En cours" :valeur="$statistiques['en_cours']" ton="teal"
                       :href="route('reservations.index', ['statut' => 'en_cours'])"/>
        <x-statistique libelle="Véhicules disponibles" :valeur="$vehiculesDisponibles"
                       variation="Disponibles à cet instant"/>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">

            <x-carte titre="Vos prochains départs">
                <x-slot:action>
                    <a href="{{ route('reservations.create') }}" class="btn-accent">Nouvelle demande</a>
                </x-slot:action>

                @forelse($prochaines as $reservation)
                    <a href="{{ route('reservations.show', $reservation) }}"
                       class="-mx-2 flex items-center gap-4 rounded-lg px-2 py-3 hover:bg-ardoise-50">
                        <div class="w-16 shrink-0 text-center">
                            <p class="font-display text-lg font-extrabold text-petro-700">{{ $reservation->date_debut->format('d') }}</p>
                            <p class="text-[11px] uppercase text-ardoise-500">{{ $reservation->date_debut->translatedFormat('M') }}</p>
                        </div>
                        <div class="min-w-0 flex-1">
                            <x-trajet :depart="$reservation->lieu_depart" :arrivee="$reservation->lieu_arrivee"/>
                            <p class="mt-0.5 truncate text-xs text-ardoise-500">
                                {{ $reservation->date_debut->format('H:i') }} ·
                                {{ $reservation->vehicule?->libelle ?? 'Véhicule en cours d\'affectation' }}
                                @if($reservation->chauffeur) · {{ $reservation->chauffeur?->nom_complet }} @endif
                            </p>
                        </div>
                        <x-badge :statut="$reservation->statut" :libelle="$reservation->statut_libelle"/>
                    </a>
                @empty
                    <x-vide titre="Aucun départ programmé"
                            message="Soumettez une demande : le responsable de flotte vous affecte un véhicule disponible."
                            action="Réserver un véhicule" :lien="route('reservations.create')"/>
                @endforelse
            </x-carte>

            <x-carte titre="Historique de vos demandes">
                <x-slot:action>
                    <a href="{{ route('reservations.index') }}" class="text-sm font-medium text-petro-700 hover:underline">Tout voir</a>
                </x-slot:action>

                <div class="overflow-x-auto">
                    <table class="tableau">
                        <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Trajet</th>
                            <th>Départ</th>
                            <th>Statut</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($recentes as $reservation)
                            <tr class="cursor-pointer" onclick="window.location='{{ route('reservations.show', $reservation) }}'">
                                <td class="font-mono text-xs font-semibold text-petro-700">{{ $reservation->code }}</td>
                                <td><x-trajet :depart="$reservation->lieu_depart" :arrivee="$reservation->lieu_arrivee" compact/></td>
                                <td class="whitespace-nowrap text-ardoise-600">{{ $reservation->date_debut->format('d/m/Y H:i') }}</td>
                                <td><x-badge :statut="$reservation->statut" :libelle="$reservation->statut_libelle"/></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-sm text-ardoise-500">Aucune demande enregistrée.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-carte>
        </div>

        <div class="space-y-6">
            <x-carte titre="Comment ça marche">
                <ol class="space-y-4 text-sm">
                    @foreach([
                        'Vous décrivez votre besoin : dates, trajet,  avec ou sans chauffeur.',
                        'Le responsable de flotte vérifie la disponibilité et valide, ou propose une alternative.',
                        'Vous recevez la confirmation par e-mail et notification, avec le véhicule et le chauffeur affectés.',
                        'À la fin de le déplacement, vous pouvez évaluer la prestation.',
                    ] as $index => $etape)
                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-petro-100 text-xs font-bold text-petro-700">
                                {{ $index + 1 }}
                            </span>
                            <span class="text-ardoise-600">{{ $etape }}</span>
                        </li>
                    @endforeach
                </ol>
                <a href="{{ route('reservations.create') }}" class="btn-primaire mt-6 w-full">Réserver un véhicule</a>
            </x-carte>

            <x-carte titre="Planning de la flotte">
                <p class="text-sm text-ardoise-600">
                    Consultez les créneaux déjà occupés avant de soumettre votre demande : vous gagnerez un aller-retour de validation.
                </p>
                <a href="{{ route('planning.index') }}" class="btn-secondaire mt-4 w-full">Voir les disponibilités</a>
            </x-carte>
        </div>
    </div>
@endsection
