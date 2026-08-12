<?php

namespace App\Models;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Année académique (ex : 2025-2026) rattachée à une école.
 */
class AnneeAcademique extends Model
{
    use HasFactory, HasTenant;

    /**
     * Table migrée sous le nom `annees_academiques`.
     *
     * @var string
     */
    protected $table = 'annees_academiques';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'libelle',
        'date_debut',
        'date_fin',
        'trimestre_en_cours',
        'cloturee',
        'archivee',
    ];

    /**
     * École de l'année académique.
     */
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /**
     * @return HasMany<Trimestre>
     */
    public function trimestres(): HasMany
    {
        return $this->hasMany(Trimestre::class)->orderBy('numero');
    }

    /**
     * @return HasMany<Classe>
     */
    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class);
    }

    /**
     * @return HasMany<Bulletin>
     */
    public function bulletins(): HasMany
    {
        return $this->hasMany(Bulletin::class);
    }

    /**
     * @return HasMany<Absence>
     */
    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    /**
     * @return HasMany<Encaissement>
     */
    public function encaissements(): HasMany
    {
        return $this->hasMany(Encaissement::class);
    }

    /**
     * @return HasMany<Depense>
     */
    public function depenses(): HasMany
    {
        return $this->hasMany(Depense::class);
    }

    /**
     * @return HasMany<Salaire>
     */
    public function salaires(): HasMany
    {
        return $this->hasMany(Salaire::class);
    }

    /**
     * @return HasMany<ConseilClasse>
     */
    public function conseils(): HasMany
    {
        return $this->hasMany(ConseilClasse::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'cloturee' => 'boolean',
            'archivee' => 'boolean',
        ];
    }
}