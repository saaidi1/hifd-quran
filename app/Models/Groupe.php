<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Groupe / حلقة : classe d'étudiants encadrée par un professeur,
 * avec horaire, salle et capacité limitée. Support du تسميع quotidien
 * (rapports journaliers) et cible des affectations d'étudiants.
 */
class Groupe extends Model
{
    /** Champs assignables : identité (fr/ar), professeur responsable, planning et capacité. */
    protected $fillable = [
        'nom', 'nom_ar', 'professeur_id', 'niveau', 'salle',
        'horaire_debut', 'horaire_fin', 'capacite', 'annee_scolaire', 'actif',
    ];

    /** Capacité en entier, drapeau actif en booléen. */
    protected $casts = ['actif' => 'boolean', 'capacite' => 'integer'];

    /** Professeur (أستاذ) responsable de la حلقة. */
    public function professeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professeur_id');
    }

    /** Étudiants rattachés au groupe via groupe_id. */
    public function etudiants(): HasMany
    {
        return $this->hasMany(Etudiant::class);
    }

    /** Rapports journaliers de تسميع saisis par le professeur pour ce groupe. */
    public function rapportsJournaliers(): HasMany
    {
        return $this->hasMany(RapportJournalier::class);
    }

    /** Vrai si le nombre d'étudiants a atteint la capacité définie. */
    public function estComplet(): bool
    {
        return $this->capacite !== null && $this->etudiants()->count() >= $this->capacite;
    }

    /** Places encore disponibles avant saturation (jamais négatif). */
    public function placesRestantes(): int
    {
        return max(0, ($this->capacite ?? 0) - $this->etudiants()->count());
    }
}
