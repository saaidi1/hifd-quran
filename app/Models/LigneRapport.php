<?php

namespace App\Models;

use App\Enums\NiveauMaitrise;
use App\Enums\TypeSeance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Détail d'un rapport journalier : ce qui a réellement été récité
 * (حفظ جديد / مراجعة قريبة / مراجعة الحفظ القديم).
 */
class LigneRapport extends Model
{
    use PlageCoranique;

    protected $table = 'lignes_rapport';

    /** Champs assignables : rattachement rapport/tâche, type de séance, plage coranique
     * et mesures du تسميع (pages, note, erreurs, hésitations, maîtrise). */
    protected $fillable = [
        'rapport_id', 'tache_id', 'type',
        'sourate_debut_id', 'ayah_debut', 'sourate_fin_id', 'ayah_fin',
        'nb_pages', 'note', 'nb_erreurs', 'nb_hesitations',
        'maitrise', 'observation',
    ];

    /** Type et maîtrise via enums, pages/note en décimal 2. */
    protected $casts = [
        'type'     => TypeSeance::class,
        'maitrise' => NiveauMaitrise::class,
        'nb_pages' => 'decimal:2',
        'note'     => 'decimal:2',
    ];

    /** Rapport journalier parent (une ligne par lecture réalisée). */
    public function rapport(): BelongsTo { return $this->belongsTo(RapportJournalier::class, 'rapport_id'); }

    /** واجب مقرر éventuellement à l'origine de cette séance. */
    public function tache(): BelongsTo   { return $this->belongsTo(TacheMemorisation::class, 'tache_id'); }

    /** Sourate de début de la plage récitée (trait PlageCoranique). */
    public function sourateDebut(): BelongsTo { return $this->belongsTo(Sourate::class, 'sourate_debut_id'); }

    /** Sourate de fin de la plage récitée (trait PlageCoranique). */
    public function sourateFin(): BelongsTo   { return $this->belongsTo(Sourate::class, 'sourate_fin_id'); }

    /* ---------------- Événements ---------------- */

    /** À l'enregistrement : déduit le niveau de maîtrise depuis la note si absent ;
     * après sauvegarde/suppression : recalcule la note globale du rapport parent. */
    protected static function booted(): void
    {
        static::saving(function (self $ligne) {
            if ($ligne->note !== null && $ligne->maitrise === null) {
                $ligne->maitrise = NiveauMaitrise::depuisNote((float) $ligne->note);
            }
        });

        static::saved(fn (self $l) => $l->rapport?->recalculerNoteGlobale());
        static::deleted(fn (self $l) => $l->rapport?->recalculerNoteGlobale());
    }
}
