<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Litige extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'reservation_id', 'vehicule_id', 'declare_par', 'type', 'objet',
        'description', 'gravite', 'statut', 'resolution', 'traite_par', 'resolu_at',
    ];

    protected $casts = ['resolu_at' => 'datetime'];

    public const TYPES = [
        'annulation' => 'Annulation',
        'retard' => 'Retard',
        'dommage' => 'Dommage véhicule',
        'facturation' => 'Facturation',
        'comportement' => 'Comportement',
        'autre' => 'Autre',
    ];

    public const STATUTS = [
        'ouvert' => 'Ouvert',
        'en_traitement' => 'En traitement',
        'resolu' => 'Résolu',
        'clos' => 'Clos',
    ];

    protected static function booted(): void
    {
        static::creating(function (Litige $litige) {
            $litige->reference ??= self::genererReference();
        });
    }

    public static function genererReference(): string
    {
        $annee = now()->format('Y');
        $dernier = self::where('reference', 'like', "LIT-{$annee}-%")->max('reference');
        $numero = $dernier ? ((int) substr($dernier, -4)) + 1 : 1;

        return sprintf('LIT-%s-%04d', $annee, $numero);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class)->withTrashed();
    }

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class)->withTrashed();
    }

    public function declarant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declare_par')->withTrashed();
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par')->withTrashed();
    }

    public function getStatutLibelleAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function getTypeLibelleAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
