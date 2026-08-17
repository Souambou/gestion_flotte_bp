<?php

namespace App\Models;

use App\Support\Departements;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'user_id', 'vehicule_id', 'chauffeur_id', 'avec_chauffeur',
        'type_deplacement', 'departement', 'date_debut', 'date_fin', 'lieu_depart', 'lieu_arrivee',
        'depart_latitude', 'depart_longitude', 'arrivee_latitude', 'arrivee_longitude',
        'distance_estimee_km', 'duree_estimee_min', 'motif', 'statut',
        'motif_refus', 'alternative_proposee', 'traite_par', 'traite_at',
        'annule_par', 'annule_at', 'motif_annulation',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'traite_at' => 'datetime',
        'annule_at' => 'datetime',
        'avec_chauffeur' => 'boolean',
    ];

    public const STATUTS = [
        'en_attente' => 'En attente',
        'validee' => 'Validée',
        'refusee' => 'Refusée',
        'en_cours' => 'En cours',
        'terminee' => 'Terminée',
        'annulee' => 'Annulée',
    ];

    /**
     * Ordre d'affichage des demandes dans les listes.
     * Ce qui appelle une action du responsable remonte en tete.
     */
    public const ORDRE_STATUTS = ['en_attente', 'validee', 'en_cours', 'refusee', 'annulee', 'terminee'];

    protected static function booted(): void
    {
        static::creating(function (Reservation $reservation) {
            $reservation->code ??= self::genererCode();
        });
    }

    public static function genererCode(): string
    {
        $annee = now()->format('Y');
        $dernier = self::withTrashed()->where('code', 'like', "RES-{$annee}-%")->max('code');
        $numero = $dernier ? ((int) substr($dernier, -5)) + 1 : 1;

        return sprintf('RES-%s-%05d', $annee, $numero);
    }

    // ----------------------------------------------------------------- Relations

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class)->withTrashed();
    }

    public function chauffeur(): BelongsTo
    {
        return $this->belongsTo(Chauffeur::class)->withTrashed();
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par')->withTrashed();
    }

    public function deplacement(): HasOne
    {
        return $this->hasOne(Deplacement::class);
    }

    public function avis(): HasOne
    {
        return $this->hasOne(Avis::class);
    }

    public function litiges()
    {
        return $this->hasMany(Litige::class);
    }

    // ----------------------------------------------------------------- Accesseurs

    public function getStatutLibelleAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function getDureeHeuresAttribute(): float
    {
        return round($this->date_debut->diffInMinutes($this->date_fin) / 60, 1);
    }

    public function getTrajetAttribute(): string
    {
        return "{$this->lieu_depart} → {$this->lieu_arrivee}";
    }

    public function getTypeDeplacementLibelleAttribute(): string
    {
        return config('beninpetro.types_deplacement')[$this->type_deplacement] ?? $this->type_deplacement;
    }

    /** Nom complet du service demandeur : affichage a l'ecran. */
    public function getDepartementLibelleAttribute(): string
    {
        return Departements::libelle($this->departement);
    }

    /** Forme abregee : rapports et exports, ou l'espace est compte. */
    public function getDepartementSigleAttribute(): string
    {
        return Departements::sigle($this->departement);
    }

    /** Une demande est consideree satisfaite des lors qu'elle a ete honoree. */
    public function getEstSatisfaiteAttribute(): bool
    {
        return in_array($this->statut, ['validee', 'en_cours', 'terminee']);
    }

    // ----------------------------------------------------------------- Scopes

    public function scopeEnAttente(Builder $q): Builder
    {
        return $q->where('statut', 'en_attente');
    }

    public function scopeActives(Builder $q): Builder
    {
        return $q->whereIn('statut', ['validee', 'en_cours']);
    }

    public function scopeDuMois(Builder $q, ?Carbon $mois = null): Builder
    {
        $mois ??= now();

        return $q->whereBetween('date_debut', [$mois->copy()->startOfMonth(), $mois->copy()->endOfMonth()]);
    }

    public function scopePeriode(Builder $q, $debut, $fin): Builder
    {
        return $q->where('date_debut', '<', $fin)->where('date_fin', '>', $debut);
    }

    /**
     * Tri metier : les demandes en attente d'abord, puis les demandes actives,
     * et enfin celles qui sont closes. A statut egal, la plus recente d'abord.
     */
    public function scopeTriMetier(Builder $q): Builder
    {
        $cas = collect(self::ORDRE_STATUTS)
            ->map(fn ($statut, $rang) => "WHEN '{$statut}' THEN {$rang}")
            ->implode(' ');

        return $q->orderByRaw("CASE statut {$cas} ELSE 99 END")
            ->orderByDesc('date_debut');
    }

    public function scopeDuDepartement(Builder $q, ?string $departement): Builder
    {
        return $departement ? $q->where('departement', $departement) : $q;
    }

    public function scopePourUtilisateur(Builder $q, User $user): Builder
    {
        // Un commercial ne voit que ses propres demandes.
        if ($user->estCommercial() && ! $user->can('reservations.voir_toutes')) {
            return $q->where('user_id', $user->id);
        }

        return $q;
    }

    // ----------------------------------------------------------------- Metier

    public function peutEtreValidee(): bool
    {
        return $this->statut === 'en_attente';
    }

    public function peutEtreAnnulee(): bool
    {
        return in_array($this->statut, ['en_attente', 'validee']);
    }

    public function peutEtreModifiee(): bool
    {
        return in_array($this->statut, ['en_attente', 'validee']);
    }

    public function estAnnulableSansPenalite(): bool
    {
        return now()->diffInHours($this->date_debut, false) >= config('beninpetro.reservation.delai_annulation_heures');
    }

    public function couleurStatut(): string
    {
        return match ($this->statut) {
            'en_attente' => 'ambre',
            'validee' => 'vert',
            'en_cours' => 'teal',
            'terminee' => 'ardoise',
            'refusee', 'annulee' => 'rouge',
            default => 'ardoise',
        };
    }
}
