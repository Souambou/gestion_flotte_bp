<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class Vehicule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'immatriculation', 'marque', 'modele', 'type', 'annee', 'carburant',
        'nombre_places', 'kilometrage', 'statut', 'agence_id', 'photo',
        'date_mise_en_service', 'date_expiration_assurance', 'date_visite_technique',
        'date_prochaine_maintenance', 'km_prochaine_maintenance',
        'latitude', 'longitude', 'position_maj_at', 'observations',
    ];

    protected $casts = [
        'date_mise_en_service' => 'date',
        'date_expiration_assurance' => 'date',
        'date_visite_technique' => 'date',
        'date_prochaine_maintenance' => 'date',
        'position_maj_at' => 'datetime',
    ];

    public const STATUTS = [
        'disponible' => 'Disponible',
        'occupe' => 'Déjà occupé',
        'en_deplacement' => 'En déplacement',
        'en_maintenance' => 'En maintenance',
        'hors_service' => 'Hors service',
    ];

    /** Statuts interdisant toute nouvelle affectation. */
    public const STATUTS_NON_RESERVABLES = ['occupe', 'en_maintenance', 'hors_service'];

    public const TYPES = [
        'berline' => 'Berline',
        'suv' => 'SUV / 4x4',
        'pickup' => 'Pick-up',
        'camion_citerne' => 'Camion citerne',
        'utilitaire' => 'Utilitaire',
        'minibus' => 'Minibus',
        'moto' => 'Moto',
    ];

    public const CARBURANTS = [
        'essence' => 'Essence',
        'gasoil' => 'Gasoil',
        'hybride' => 'Hybride',
        'electrique' => 'Électrique',
    ];

    // ----------------------------------------------------------------- Relations

    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agence::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function deplacements(): HasMany
    {
        return $this->hasMany(Deplacement::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    // ----------------------------------------------------------------- Accesseurs

    public function getLibelleAttribute(): string
    {
        return "{$this->marque} {$this->modele} — {$this->immatriculation}";
    }

    public function getTypeLibelleAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatutLibelleAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? Storage::url($this->photo) : null;
    }

    // ----------------------------------------------------------------- Scopes

    public function scopeDisponibles(Builder $query): Builder
    {
        return $query->where('statut', 'disponible');
    }

    /** Vehicules pouvant recevoir une affectation, quel que soit le creneau. */
    public function scopeReservables(Builder $query): Builder
    {
        return $query->whereNotIn('statut', self::STATUTS_NON_RESERVABLES);
    }

    public function getEstReservableAttribute(): bool
    {
        return ! in_array($this->statut, self::STATUTS_NON_RESERVABLES);
    }

    public function scopeRecherche(Builder $query, ?string $terme): Builder
    {
        if (! $terme) {
            return $query;
        }

        return $query->where(function ($q) use ($terme) {
            $q->where('immatriculation', 'like', "%{$terme}%")
                ->orWhere('marque', 'like', "%{$terme}%")
                ->orWhere('modele', 'like', "%{$terme}%");
        });
    }

    /**
     * Vehicules libres sur une periode donnee (aucune reservation active qui chevauche).
     */
    public function scopeLibresSur(Builder $query, $debut, $fin, ?int $ignorerReservationId = null): Builder
    {
        return $query->whereNotIn('statut', self::STATUTS_NON_RESERVABLES)
            ->whereDoesntHave('reservations', function ($q) use ($debut, $fin, $ignorerReservationId) {
                $q->whereIn('statut', ['validee', 'en_cours'])
                    ->where('date_debut', '<', $fin)
                    ->where('date_fin', '>', $debut);

                if ($ignorerReservationId) {
                    $q->where('id', '!=', $ignorerReservationId);
                }
            });
    }

    // ----------------------------------------------------------------- Metier

    public function estLibreSur($debut, $fin, ?int $ignorerReservationId = null): bool
    {
        if (in_array($this->statut, self::STATUTS_NON_RESERVABLES)) {
            return false;
        }

        return ! $this->reservations()
            ->whereIn('statut', ['validee', 'en_cours'])
            ->when($ignorerReservationId, fn ($q) => $q->where('id', '!=', $ignorerReservationId))
            ->where('date_debut', '<', $fin)
            ->where('date_fin', '>', $debut)
            ->exists();
    }

    /**
     * Alertes documentaires et mecaniques du vehicule.
     */
    public function alertes(): array
    {
        $alertes = [];
        $seuilJours = (int) config('beninpetro.maintenance.alerte_jours');
        $seuilKm = (int) config('beninpetro.maintenance.alerte_km');

        if ($this->date_expiration_assurance) {
            if ($this->date_expiration_assurance->isPast()) {
                $alertes[] = ['niveau' => 'danger', 'message' => 'Assurance expirée le '.$this->date_expiration_assurance->format('d/m/Y')];
            } elseif ($this->date_expiration_assurance->diffInDays(now()) <= $seuilJours) {
                $alertes[] = ['niveau' => 'attention', 'message' => 'Assurance à renouveler avant le '.$this->date_expiration_assurance->format('d/m/Y')];
            }
        }

        if ($this->date_visite_technique) {
            if ($this->date_visite_technique->isPast()) {
                $alertes[] = ['niveau' => 'danger', 'message' => 'Visite technique expirée le '.$this->date_visite_technique->format('d/m/Y')];
            } elseif ($this->date_visite_technique->diffInDays(now()) <= $seuilJours) {
                $alertes[] = ['niveau' => 'attention', 'message' => 'Visite technique à prévoir avant le '.$this->date_visite_technique->format('d/m/Y')];
            }
        }

        if ($this->date_prochaine_maintenance && $this->date_prochaine_maintenance->lte(Carbon::now()->addDays($seuilJours))) {
            $alertes[] = ['niveau' => 'attention', 'message' => 'Maintenance prévue le '.$this->date_prochaine_maintenance->format('d/m/Y')];
        }

        if ($this->km_prochaine_maintenance && ($this->km_prochaine_maintenance - $this->kilometrage) <= $seuilKm) {
            $restant = max(0, $this->km_prochaine_maintenance - $this->kilometrage);
            $alertes[] = ['niveau' => 'attention', 'message' => "Révision dans {$restant} km"];
        }

        return $alertes;
    }

    /**
     * Taux d'occupation du vehicule sur une periode, en pourcentage.
     */
    public function tauxOccupation($debut, $fin): float
    {
        $debut = Carbon::parse($debut);
        $fin = Carbon::parse($fin);
        $minutesPeriode = max(1, $debut->diffInMinutes($fin));

        $minutesOccupees = $this->reservations()
            ->whereIn('statut', ['validee', 'en_cours', 'terminee'])
            ->where('date_debut', '<', $fin)
            ->where('date_fin', '>', $debut)
            ->get()
            ->sum(function (Reservation $r) use ($debut, $fin) {
                $d = $r->date_debut->max($debut);
                $f = $r->date_fin->min($fin);

                return max(0, $d->diffInMinutes($f));
            });

        return round(min(100, $minutesOccupees / $minutesPeriode * 100), 1);
    }
}
