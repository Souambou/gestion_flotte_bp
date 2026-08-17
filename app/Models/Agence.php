<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agence extends Model
{
    use HasFactory;

    protected $table = 'agences';

    protected $fillable = [
        'nom', 'ville', 'adresse', 'telephone', 'latitude', 'longitude', 'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function vehicules(): HasMany
    {
        return $this->hasMany(Vehicule::class);
    }

    public function chauffeurs(): HasMany
    {
        return $this->hasMany(Chauffeur::class);
    }

    public function utilisateurs(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
