<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Parametre extends Model
{
    protected $table = 'parametres';

    protected $fillable = ['cle', 'valeur', 'groupe', 'libelle', 'type', 'description', 'chiffre'];

    protected $casts = ['chiffre' => 'boolean'];

    public const CACHE_KEY = 'parametres.application';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Valeur en clair (dechiffree si necessaire).
     */
    public function getValeurClaireAttribute(): ?string
    {
        if (! $this->valeur) {
            return null;
        }

        if (! $this->chiffre) {
            return $this->valeur;
        }

        try {
            return Crypt::decryptString($this->valeur);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getValeurMasqueeAttribute(): ?string
    {
        $valeur = $this->valeur_claire;

        if (! $valeur) {
            return null;
        }

        return str_repeat('•', max(0, strlen($valeur) - 4)).substr($valeur, -4);
    }

    public static function definir(string $cle, ?string $valeur): void
    {
        $parametre = self::firstOrNew(['cle' => $cle]);
        $parametre->valeur = ($valeur !== null && $parametre->chiffre) ? Crypt::encryptString($valeur) : $valeur;
        $parametre->libelle = $parametre->libelle ?: $cle;
        $parametre->save();
    }

    public static function valeur(string $cle, $defaut = null)
    {
        $tous = Cache::rememberForever(self::CACHE_KEY, fn () => self::all()->keyBy('cle'));

        $parametre = $tous->get($cle);

        return $parametre?->valeur_claire ?: $defaut;
    }
}
