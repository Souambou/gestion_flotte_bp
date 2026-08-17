<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicule_id', 'type', 'intitule', 'description', 'date_prevue', 'date_realisee',
        'kilometrage', 'cout', 'prestataire', 'statut', 'cree_par',
    ];

    protected $casts = [
        'date_prevue' => 'date',
        'date_realisee' => 'date',
        'cout' => 'decimal:2',
    ];

    public const TYPES = [
        'preventive' => 'Préventive',
        'corrective' => 'Corrective',
        'revision' => 'Révision',
        'reparation' => 'Réparation',
        'controle_technique' => 'Contrôle technique',
    ];

    public const STATUTS = [
        'planifiee' => 'Planifiée',
        'en_cours' => 'En cours',
        'terminee' => 'Terminée',
        'annulee' => 'Annulée',
    ];

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class)->withTrashed();
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cree_par')->withTrashed();
    }

    public function getTypeLibelleAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatutLibelleAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }
}
