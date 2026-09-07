<?php

namespace App\Models;

use App\Enums\TypeComportement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** السلوك والملاحظات : incidents, encouragements et sanctions. */
class Comportement extends Model
{
    /** Champs assignables : signalement (auteur, date, type), qualification (gravité, points)
     * puis traitement éventuel (sanction, traiteur, date). */
    protected $fillable = [
        'etudiant_id', 'signale_par', 'date', 'type', 'categorie',
        'gravite', 'points', 'description', 'sanction', 'traite_par', 'date_traitement',
    ];

    /** Type via enum TypeComportement, dates en Carbon, gravité/points en entiers. */
    protected $casts = [
        'type'            => TypeComportement::class,
        'date'            => 'date',
        'date_traitement' => 'date',
        'gravite'         => 'integer',
        'points'          => 'integer',
    ];

    /** Étudiant concerné par le signalement. */
    public function etudiant(): BelongsTo  { return $this->belongsTo(Etudiant::class); }

    /** Professeur/superviseur ayant signalé le comportement. */
    public function signalePar(): BelongsTo { return $this->belongsTo(User::class, 'signale_par'); }

    /** Membre de l'encadrement ayant traité le cas (sanction ou levée). */
    public function traitePar(): BelongsTo  { return $this->belongsTo(User::class, 'traite_par'); }
}
