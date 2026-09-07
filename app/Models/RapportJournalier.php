<?php

namespace App\Models;

use App\Enums\NiveauComportement;
use App\Enums\StatutPresence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * التقرير اليومي : une ligne par étudiant, par jour et par séance, saisie par le professeur.
 * Unicité métier : (etudiant_id, date, seance) ; la seance est numérotée max+1 pour le même jour.
 * Le détail (plusieurs lectures : hifd jadid / murajaa...) est porté par les LigneRapport,
 * dont la moyenne alimente note_globale via recalculerNoteGlobale().
 */
class RapportJournalier extends Model
{
    protected $table = 'rapports_journaliers';

    /** Champs assignables : contexte (étudiant, professeur, groupe, date, seance),
     * présence et évaluations (comportement, note globale, remarques). */
    protected $fillable = [
        'etudiant_id', 'professeur_id', 'groupe_id', 'date', 'seance',
        'presence', 'heure_arrivee', 'note_comportement',
        'note_globale', 'remarques',
    ];

    /** Date en Carbon, présence/comportement via enums, note globale en décimal 2. */
    protected $casts = [
        'date'              => 'date',
        'presence'          => StatutPresence::class,
        'note_comportement' => NiveauComportement::class,
        'note_globale'      => 'decimal:2',
    ];

    /** Étudiant concerné par ce rapport de تسميع. */
    public function etudiant(): BelongsTo   { return $this->belongsTo(Etudiant::class); }

    /** Professeur (أستاذ) ayant saisi le rapport. */
    public function professeur(): BelongsTo { return $this->belongsTo(User::class, 'professeur_id'); }

    /** Groupe (حلقة) dans lequel la séance a eu lieu. */
    public function groupe(): BelongsTo     { return $this->belongsTo(Groupe::class); }

    /** Lignes détaillées : chaque lecture réellement réalisée (hifd_jadid/hifd_qadim...). */
    public function lignes(): HasMany       { return $this->hasMany(LigneRapport::class, 'rapport_id'); }

    /** Recalcule la moyenne du jour à partir des lignes. */
    public function recalculerNoteGlobale(): void
    {
        $moyenne = $this->lignes()->avg('note');
        $this->update(['note_globale' => $moyenne ? round($moyenne, 2) : null]);
    }

    /** Filtre les rapports entre deux dates (pour les synthèses périodiques). */
    public function scopePeriode($q, $debut, $fin)
    {
        return $q->whereBetween('date', [$debut, $fin]);
    }
}
