<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Chauffeur extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'matricule', 'nom', 'prenom', 'telephone', 'email', 'numero_permis',
        'categorie_permis', 'date_expiration_permis', 'date_embauche', 'statut',
        'agence_id', 'user_id', 'photo', 'observations',
    ];

    protected $casts = [
        'date_expiration_permis' => 'date',
        'date_embauche' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Chauffeur $chauffeur) {
            $chauffeur->matricule ??= self::genererMatricule();
        });
    }

    /**
     * Matricule attribue automatiquement, au format CH-0001.
     * On repart du plus grand numero existant, corbeille comprise, afin
     * qu'un matricule ne soit jamais reattribue apres une suppression.
     */
    public static function genererMatricule(): string
    {
        $dernier = self::withTrashed()
            ->where('matricule', 'like', 'CH-%')
            ->max('matricule');

        $numero = $dernier ? ((int) substr($dernier, 3)) + 1 : 1;

        return sprintf('CH-%04d', $numero);
    }

    public const STATUTS = [
        'disponible' => 'Disponible',
        'en_deplacement' => 'En déplacement',
        'indisponible' => 'Indisponible',
        'conge' => 'En congé',
    ];

    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agence::class);
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function deplacements(): HasMany
    {
        return $this->hasMany(Deplacement::class);
    }

    public function getNomCompletAttribute(): string
    {
        return trim($this->prenom.' '.$this->nom);
    }

    public function getStatutLibelleAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? Storage::url($this->photo) : null;
    }

    public function getPermisExpireAttribute(): bool
    {
        return $this->date_expiration_permis && $this->date_expiration_permis->isPast();
    }

    public function scopeDisponibles(Builder $query): Builder
    {
        return $query->where('statut', 'disponible');
    }

    public function scopeRecherche(Builder $query, ?string $terme): Builder
    {
        if (! $terme) {
            return $query;
        }

        return $query->where(function ($q) use ($terme) {
            $q->where('nom', 'like', "%{$terme}%")
                ->orWhere('prenom', 'like', "%{$terme}%")
                ->orWhere('matricule', 'like', "%{$terme}%")
                ->orWhere('telephone', 'like', "%{$terme}%");
        });
    }

    public function scopeLibresSur(Builder $query, $debut, $fin, ?int $ignorerReservationId = null): Builder
    {
        return $query->whereIn('statut', ['disponible', 'en_deplacement'])
            ->whereDoesntHave('reservations', function ($q) use ($debut, $fin, $ignorerReservationId) {
                $q->whereIn('statut', ['validee', 'en_cours'])
                    ->where('date_debut', '<', $fin)
                    ->where('date_fin', '>', $debut);

                if ($ignorerReservationId) {
                    $q->where('id', '!=', $ignorerReservationId);
                }
            });
    }

    public function estLibreSur($debut, $fin, ?int $ignorerReservationId = null): bool
    {
        if (in_array($this->statut, ['indisponible', 'conge'])) {
            return false;
        }

        return ! $this->reservations()
            ->whereIn('statut', ['validee', 'en_cours'])
            ->when($ignorerReservationId, fn ($q) => $q->where('id', '!=', $ignorerReservationId))
            ->where('date_debut', '<', $fin)
            ->where('date_fin', '>', $debut)
            ->exists();
    }
}
