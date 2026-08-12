<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cycle d'enseignement (Maternelle, Primaire, Collège, Lycée).
 */
class Cycle extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'libelle',
        'ordre',
    ];

    /**
     * @return HasMany<Niveau>
     */
    public function niveaux(): HasMany
    {
        return $this->hasMany(Niveau::class)->orderBy('ordre');
    }
}