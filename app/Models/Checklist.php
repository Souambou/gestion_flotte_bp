<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Controle matinal de l'etat d'un vehicule.
 *
 * Le controle n'est plus rattache a un deplacement : il est realise chaque
 * matin sur chaque vehicule de la flotte, independamment des reservations.
 * Un seul controle par vehicule et par jour.
 */
class Checklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicule_id', 'date_controle', 'user_id', 'kilometrage',
        'niveau_carburant', 'etat_general', 'points', 'photos',
        'anomalies', 'commentaire', 'signature', 'completee_at',
    ];

    protected $casts = [
        'date_controle' => 'date',
        'points' => 'array',
        'photos' => 'array',
        'completee_at' => 'datetime',
    ];

    public const ETATS_POINT = [
        'conforme' => 'Conforme',
        'a_surveiller' => 'À surveiller',
        'non_conforme' => 'Non conforme',
        'absent' => 'Absent',
    ];

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class)->withTrashed();
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    /** Controles d'une journee donnee. */
    public function scopeDuJour(Builder $requete, $jour = null): Builder
    {
        return $requete->whereDate('date_controle', $jour ?? today());
    }

    public function scopeCompletees(Builder $requete): Builder
    {
        return $requete->whereNotNull('completee_at');
    }

    public function getLibelleAttribute(): string
    {
        return 'Contrôle du '.$this->date_controle->format('d/m/Y');
    }

    public function getEstDuJourAttribute(): bool
    {
        return $this->date_controle->isToday();
    }

    public function getNombreAnomaliesAttribute(): int
    {
        return collect($this->points ?? [])
            ->filter(fn ($p) => in_array($p['statut'] ?? '', ['non_conforme', 'absent']))
            ->count();
    }

    public function getTauxConformiteAttribute(): float
    {
        $points = collect($this->points ?? []);

        if ($points->isEmpty()) {
            return 0;
        }

        $conformes = $points->filter(fn ($p) => ($p['statut'] ?? '') === 'conforme')->count();

        return round($conformes / $points->count() * 100, 1);
    }

    /** Vehicules de la flotte n'ayant pas encore ete controles pour la journee. */
    public static function vehiculesRestants($jour = null)
    {
        $jour = $jour ? Carbon::parse($jour) : today();

        $controles = static::duJour($jour)->pluck('vehicule_id');

        return Vehicule::where('statut', '!=', 'hors_service')
            ->whereNotIn('id', $controles)
            ->orderBy('immatriculation')
            ->get();
    }
}
