<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Deplacement effectif : cree a la validation d'une reservation, il porte
 * le suivi terrain (ouverture, kilometrage, cloture, couts).
 *
 * Le controle d'etat du vehicule n'est plus rattache au deplacement :
 * il est realise chaque matin sur l'ensemble de la flotte (cf. Checklist).
 */
class Deplacement extends Model
{
    use HasFactory;

    protected $table = 'deplacements';

    protected $fillable = [
        'code', 'reservation_id', 'vehicule_id', 'chauffeur_id',
        'depart_reel_at', 'arrivee_reelle_at', 'km_depart', 'km_arrivee',
        'carburant_consomme', 'cout_carburant', 'autres_frais',
        'statut', 'observations', 'ouverte_par', 'cloturee_par',
    ];

    protected $casts = [
        'depart_reel_at' => 'datetime',
        'arrivee_reelle_at' => 'datetime',
        'carburant_consomme' => 'decimal:2',
        'cout_carburant' => 'decimal:2',
        'autres_frais' => 'decimal:2',
    ];

    public const STATUTS = [
        'planifiee' => 'Planifié',
        'en_cours' => 'En cours',
        'terminee' => 'Terminé',
        'incident' => 'Incident',
    ];

    protected static function booted(): void
    {
        static::creating(function (Deplacement $deplacement) {
            $deplacement->code ??= self::genererCode();
        });
    }

    public static function genererCode(): string
    {
        $annee = now()->format('Y');
        $dernier = self::where('code', 'like', "DEP-{$annee}-%")->max('code');
        $numero = $dernier ? ((int) substr($dernier, -5)) + 1 : 1;

        return sprintf('DEP-%s-%05d', $annee, $numero);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class)->withTrashed();
    }

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class)->withTrashed();
    }

    public function chauffeur(): BelongsTo
    {
        return $this->belongsTo(Chauffeur::class)->withTrashed();
    }

    public function ouvreur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ouverte_par')->withTrashed();
    }

    public function cloturateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cloturee_par')->withTrashed();
    }

    public function getStatutLibelleAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function getDistanceParcourueAttribute(): ?int
    {
        if ($this->km_depart === null || $this->km_arrivee === null) {
            return null;
        }

        return max(0, $this->km_arrivee - $this->km_depart);
    }

    public function getCoutTotalAttribute(): float
    {
        return (float) $this->cout_carburant + (float) $this->autres_frais;
    }

    /**
     * La cloture reste bloquee tant que les releves terrain sont incomplets.
     * Les checklists n'en font plus partie : elles sont quotidiennes.
     */
    public function elementsManquants(): array
    {
        $manquants = [];

        if ($this->km_depart === null) {
            $manquants[] = 'Kilométrage au départ';
        }
        if ($this->km_arrivee === null) {
            $manquants[] = 'Kilométrage à l\'arrivée';
        }
        if (! $this->depart_reel_at) {
            $manquants[] = 'Heure de départ réelle';
        }

        return $manquants;
    }

    public function peutEtreCloture(): bool
    {
        return $this->statut === 'en_cours' && empty($this->elementsManquants());
    }
}
