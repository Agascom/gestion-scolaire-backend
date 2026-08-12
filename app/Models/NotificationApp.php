<?php

namespace App\Models;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Notification destinée à un utilisateur (parent, enseignant, direction) et/ou à une école.
 * Canal : mail, sms, push. Uniquement consultable par le destinataire.
 */
class NotificationApp extends Model
{
    use HasTenant, SoftDeletes;

    /**
     * Table migrée sous le nom `notifications_app`.
     *
     * @var string
     */
    protected $table = 'notifications_app';

    public const TYPE_PAIEMENT_RETARD = 'paiement_retard';

    public const TYPE_BULLETIN_PUBLIE = 'bulletin_publie';

    public const TYPE_NOTE_PUBLIEE = 'note_publiee';

    public const TYPE_ABSENCE = 'absence';

    public const TYPE_CONSEIL = 'conseil';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'user_id',
        'type',
        'titre',
        'message',
        'canal',
        'lue',
        'lue_le',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Notifications non lues sur le site.
     */
    public function scopeNonLues(Builder $query): Builder
    {
        return $query->where('lue', false);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lue' => 'boolean',
            'lue_le' => 'datetime',
        ];
    }
}
