<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Test / consultation réalisé par المشرف على الأساتذة
 * avant validation ou refus de la pré-inscription.
 */
class EvaluationInscription extends Model
{
    protected $table = 'evaluations_inscription';

    /** Champs du test : notes hifd/tajwid/lecture, sourate testée et décision finale. */
    protected $fillable = [
        'etudiant_id', 'superviseur_id', 'date_test',
        'hizb_maitrise', 'note_hifd', 'note_tajwid', 'note_lecture',
        'sourate_testee_id', 'observations', 'decision', 'motif', 'niveau_propose',
    ];

    /** Dates en Carbon, notes sur 10 avec 2 décimales. */
    protected $casts = [
        'date_test'     => 'date',
        'hizb_maitrise' => 'decimal:2',
        'note_hifd'     => 'decimal:2',
        'note_tajwid'   => 'decimal:2',
        'note_lecture'  => 'decimal:2',
    ];

    /** Candidat évalué. */
    public function etudiant(): BelongsTo    { return $this->belongsTo(Etudiant::class); }

    /** Superviseur (المشرف على الأساتذة) ayant conduit le test. */
    public function superviseur(): BelongsTo { return $this->belongsTo(User::class, 'superviseur_id'); }

    /** Sourate utilisée comme support du test de lecture. */
    public function sourateTestee(): BelongsTo { return $this->belongsTo(Sourate::class, 'sourate_testee_id'); }

    /** Moyenne des trois notes (hifd + tajwid + lecture) / 3, arrondie à 2 décimales. */
    public function getMoyenneAttribute(): float
    {
        return round(((float) $this->note_hifd + (float) $this->note_tajwid + (float) $this->note_lecture) / 3, 2);
    }
}
