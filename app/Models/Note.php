<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Note d'un élève pour une évaluation donnée.
 */
class Note extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'evaluation_id',
        'eleve_id',
        'note',
        'appreciation',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }
}