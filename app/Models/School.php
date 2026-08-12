<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * École (complexe scolaire). Racine des données multi-écoles.
 */
class School extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nom',
        'sigle',
        'adresse',
        'telephone',
        'email',
        'logo_path',
        'numero_agrement',
        'statut',
    ];

    /**
     * @return HasMany<User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<AnneeAcademique>
     */
    public function anneesAcademiques(): HasMany
    {
        return $this->hasMany(AnneeAcademique::class);
    }

    /**
     * @return HasMany<Salle>
     */
    public function salles(): HasMany
    {
        return $this->hasMany(Salle::class);
    }

    /**
     * @return HasMany<Enseignant>
     */
    public function enseignants(): HasMany
    {
        return $this->hasMany(Enseignant::class);
    }

    /**
     * @return HasMany<Eleve>
     */
    public function eleves(): HasMany
    {
        return $this->hasMany(Eleve::class);
    }

    /**
     * @return HasMany<Classe>
     */
    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class);
    }

    /**
     * Paramètres clé/valeur de l'école (TVAs, seuils, coefficients...).
     *
     * @return HasMany<SchoolSetting>
     */
    public function settings(): HasMany
    {
        return $this->hasMany(SchoolSetting::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'statut' => 'boolean',
        ];
    }
}