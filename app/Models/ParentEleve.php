<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fiche parent / tuteur d'un élève.
 * Nom de classe volontairement "ParentEleve" : "Parent" est un mot réservé en PHP.
 */
class ParentEleve extends Model
{
    use HasFactory;

    /**
     * Table migrée sous le nom `parents`.
     *
     * @var string
     */
    protected $table = 'parents';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'eleve_id',
        'nom',
        'prenom',
        'telephone',
        'email',
        'profession',
        'est_tuteur',
    ];

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class, 'eleve_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'est_tuteur' => 'boolean',
        ];
    }
}