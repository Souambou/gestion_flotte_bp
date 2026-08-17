<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Acces centralise aux services de la societe.
 *
 * Deux formes coexistent pour un meme service :
 *   - le nom complet, affiche a la saisie et partout ou l'espace le permet ;
 *   - le sigle, reserve aux rapports et aux exports dont les colonnes sont
 *     nombreuses.
 *
 * Tout passe par cette classe afin que les deux formes ne divergent jamais.
 */
class Departements
{
    /** Tableau brut de configuration. */
    public static function tous(): array
    {
        return config('beninpetro.departements', []);
    }

    /** Cles techniques, pour les regles de validation. */
    public static function cles(): array
    {
        return array_keys(static::tous());
    }

    /**
     * Options d'un menu deroulant : cle => nom complet.
     * C'est cette forme qu'utilise le formulaire de reservation.
     */
    public static function options(): array
    {
        return collect(static::tous())
            ->map(fn ($d) => $d['libelle'])
            ->all();
    }

    /** Options abregees : cle => sigle. Reservees aux rapports. */
    public static function optionsAbregees(): array
    {
        return collect(static::tous())
            ->map(fn ($d) => $d['sigle'])
            ->all();
    }

    /** Nom complet d'un service ; la cle sert de repli si elle est inconnue. */
    public static function libelle(?string $cle): string
    {
        return static::tous()[$cle]['libelle'] ?? (string) $cle;
    }

    /** Sigle d'un service ; le nom complet sert de repli. */
    public static function sigle(?string $cle): string
    {
        return static::tous()[$cle]['sigle'] ?? static::libelle($cle);
    }

    /**
     * Collection prete pour les tableaux de rapport : chaque entree porte
     * sa cle, son sigle et son nom complet (utile en infobulle).
     */
    public static function pourRapport(): Collection
    {
        return collect(static::tous())->map(fn ($d, $cle) => [
            'cle' => $cle,
            'sigle' => $d['sigle'],
            'libelle' => $d['libelle'],
        ])->values();
    }
}
