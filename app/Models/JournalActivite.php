<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalActivite extends Model
{
    protected $table = 'journal_activites';

    protected $fillable = [
        'user_id', 'action', 'sujet_type', 'sujet_id', 'description', 'donnees', 'adresse_ip',
    ];

    protected $casts = ['donnees' => 'array'];

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function sujet()
    {
        return $this->morphTo(__FUNCTION__, 'sujet_type', 'sujet_id');
    }

    public static function enregistrer(string $action, ?Model $sujet = null, ?string $description = null, array $donnees = []): self
    {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'sujet_type' => $sujet ? get_class($sujet) : null,
            'sujet_id' => $sujet?->getKey(),
            'description' => $description,
            'donnees' => $donnees ?: null,
            'adresse_ip' => request()->ip(),
        ]);
    }
}
