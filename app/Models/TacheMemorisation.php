<?php

namespace App\Models;

use App\Enums\TypeSeance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * الواجب المقرر : portion assignée par le professeur à un étudiant
 * (nouveau hifd ou révision de l'ancien hifd).
 * Assignée au préalable avec une échéance ; les séances réalisées
 * (LigneRapport) s'y rattachent pour suivre la progression.
 */
class TacheMemorisation extends Model
{
    use PlageCoranique;

    protected $table = 'taches_memorisation';

    /** Champs assignables : étudiant + professeur, type (TypeSeance), plage coranique
     * (trait PlageCoranique), volume, échéancier et consignes. */
    protected $fillable = [
        'etudiant_id', 'professeur_id', 'type',
        'sourate_debut_id', 'ayah_debut', 'sourate_fin_id', 'ayah_fin',
        'nb_pages', 'date_assignation', 'date_echeance', 'consignes', 'statut',
    ];

    /** Type via enum TypeSeance, dates en Carbon, pages en décimal 2. */
    protected $casts = [
        'type'             => TypeSeance::class,
        'date_assignation' => 'date',
        'date_echeance'    => 'date',
        'nb_pages'         => 'decimal:2',
    ];

    /** Étudiant à qui le واجب مقرر est assigné. */
    public function etudiant(): BelongsTo   { return $this->belongsTo(Etudiant::class); }

    /** Professeur (أستاذ) ayant assigné la portion à mémoriser. */
    public function professeur(): BelongsTo { return $this->belongsTo(User::class, 'professeur_id'); }

    /** Séances de تسميع réalisées sur cette tâche (progression). */
    public function lignes(): HasMany       { return $this->hasMany(LigneRapport::class, 'tache_id'); }

    /** Tâches encore assignées et non échues (échéance ≥ aujourd'hui). */
    public function scopeEnCours($q)
    {
        return $q->where('statut', 'assignee')->whereDate('date_echeance', '>=', now());
    }
}
