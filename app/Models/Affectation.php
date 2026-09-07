<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Historique des affectations d'un étudiant aux groupes (حلقات). */
class Affectation extends Model
{
    /** Champs assignables : références étudiant/groupe/auteur, période de validité et motif. */
    protected $fillable = [
        'etudiant_id', 'groupe_id', 'affecte_par',
        'date_debut', 'date_fin', 'motif', 'actif',
    ];

    /** Dates en objets Carbon, drapeau actif en booléen. */
    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
        'actif'      => 'boolean',
    ];

    /** Étudiant concerné par l'affectation (الطالب). */
    public function etudiant(): BelongsTo   { return $this->belongsTo(Etudiant::class); }

    /** Groupe (حلقة) cible de l'affectation. */
    public function groupe(): BelongsTo     { return $this->belongsTo(Groupe::class); }

    /** Utilisateur (directeur/superviseur) ayant réalisé l'affectation. */
    public function affectePar(): BelongsTo { return $this->belongsTo(User::class, 'affecte_par'); }
}
