<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'matricule', 'nom', 'prenom', 'email', 'telephone', 'poste',
        'departement', 'agence_id', 'photo', 'password', 'actif', 'doit_changer_mot_de_passe',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'derniere_connexion_at' => 'datetime',
            'password' => 'hashed',
            'actif' => 'boolean',
            'doit_changer_mot_de_passe' => 'boolean',
        ];
    }

    // ----------------------------------------------------------------- Relations

    /** Nom complet du service de rattachement. */
    public function getDepartementLibelleAttribute(): ?string
    {
        return $this->departement ? \App\Support\Departements::libelle($this->departement) : null;
    }


    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agence::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function reservationsTraitees(): HasMany
    {
        return $this->hasMany(Reservation::class, 'traite_par');
    }

    public function litiges(): HasMany
    {
        return $this->hasMany(Litige::class, 'declare_par');
    }

    public function activites(): HasMany
    {
        return $this->hasMany(JournalActivite::class);
    }

    // ----------------------------------------------------------------- Accesseurs

    public function getNomCompletAttribute(): string
    {
        return trim($this->prenom.' '.$this->nom);
    }

    public function getInitialesAttribute(): string
    {
        $initiales = mb_substr($this->prenom ?: $this->nom, 0, 1).mb_substr($this->nom, 0, 1);

        return mb_strtoupper($initiales);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? Storage::url($this->photo) : null;
    }

    public function getRoleLibelleAttribute(): string
    {
        $role = $this->roles->first()?->name;

        return config('beninpetro.roles.'.$role, $role ?? 'Utilisateur');
    }

    // ----------------------------------------------------------------- Scopes

    public function scopeActifs($query)
    {
        return $query->where('actif', true);
    }

    public function scopeRecherche($query, ?string $terme)
    {
        if (! $terme) {
            return $query;
        }

        return $query->where(function ($q) use ($terme) {
            $q->where('nom', 'like', "%{$terme}%")
                ->orWhere('prenom', 'like', "%{$terme}%")
                ->orWhere('email', 'like', "%{$terme}%")
                ->orWhere('matricule', 'like', "%{$terme}%");
        });
    }

    // ----------------------------------------------------------------- Helpers

    public function estAdministrateur(): bool
    {
        return $this->hasRole('administrateur');
    }

    public function estResponsableFlotte(): bool
    {
        return $this->hasRole('responsable_flotte');
    }

    public function estCommercial(): bool
    {
        return $this->hasRole('commercial');
    }
}
