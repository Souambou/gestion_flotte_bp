<?php

namespace App\Services;

use App\Models\Parametre;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Enveloppe autour des API Google Maps (Geocoding + Distance Matrix).
 * La cle est saisie dans Parametres > Integrations ; a defaut, la valeur .env est utilisee.
 */
class ServiceGoogleMaps
{
    public function cle(): ?string
    {
        return Parametre::valeur('google_maps_api_key', config('services.google_maps.key'));
    }

    public function estConfigure(): bool
    {
        return ! empty($this->cle());
    }

    /** Convertit une adresse en coordonnees. */
    public function geocoder(string $adresse): ?array
    {
        if (! $this->estConfigure()) {
            return null;
        }

        return Cache::remember('geocode:'.md5($adresse), now()->addDays(30), function () use ($adresse) {
            try {
                $reponse = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $adresse,
                    'region' => 'bj',
                    'key' => $this->cle(),
                ]);

                $resultat = $reponse->json('results.0.geometry.location');

                return $resultat ? ['latitude' => $resultat['lat'], 'longitude' => $resultat['lng']] : null;
            } catch (\Throwable $e) {
                Log::error('Géocodage impossible : '.$e->getMessage());

                return null;
            }
        });
    }

    /** Distance (km) et duree (min) estimees entre deux adresses. */
    public function distance(string $origine, string $destination): ?array
    {
        if (! $this->estConfigure()) {
            return null;
        }

        return Cache::remember('distance:'.md5($origine.'|'.$destination), now()->addDays(7), function () use ($origine, $destination) {
            try {
                $reponse = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                    'origins' => $origine,
                    'destinations' => $destination,
                    'region' => 'bj',
                    'key' => $this->cle(),
                ]);

                $element = $reponse->json('rows.0.elements.0');

                if (! $element || ($element['status'] ?? '') !== 'OK') {
                    return null;
                }

                return [
                    'distance_km' => (int) round($element['distance']['value'] / 1000),
                    'duree_min' => (int) round($element['duration']['value'] / 60),
                ];
            } catch (\Throwable $e) {
                Log::error('Calcul de distance impossible : '.$e->getMessage());

                return null;
            }
        });
    }
}
