<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Matière du référentiel (Mathématiques, Français...).
 */
class Matiere extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'libelle',
        'abreviation',
        'coefficient_par_defaut',
    ];

    /**
     * @return HasMany<MatiereClasse>
     */
    public function matiereClasses(): HasMany
    {
        return $this->hasMany(MatiereClasse::class);
    }

    /**
     * Classes utilisant cette matière via l'affectation matiere_classe.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<Classe>
     */
    public function classes()
    {
        return $this->belongsToMany(Classe::class, 'matiere_classe');
    }
}