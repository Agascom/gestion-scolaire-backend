<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Niveau d'enseignement rattaché à un cycle (CP1, 6eme, Terminale...).
 */
class Niveau extends Model
{
    use HasFactory;

    /**
     * Table migrée sous le nom `niveaux`.
     *
     * @var string
     */
    protected $table = 'niveaux';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cycle_id',
        'libelle',
        'ordre',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    /**
     * @return HasMany<Classe>
     */
    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class);
    }
}