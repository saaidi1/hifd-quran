<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** الإيواء : رقم الغرفة ورقم السرير */
class Hebergement extends Model
{
    /** Champs assignables : étudiant interne, chambre + numéro de lit (= matricule), période. */
    protected $fillable = [
        'etudiant_id', 'chambre_id', 'numero_lit',
        'date_debut', 'date_fin', 'attribue_par', 'observation', 'actif',
    ];

    /** Dates en Carbon, drapeau actif en booléen. */
    protected $casts = ['date_debut' => 'date', 'date_fin' => 'date', 'actif' => 'boolean'];

    /** Étudiant hébergé (interne). */
    public function etudiant(): BelongsTo    { return $this->belongsTo(Etudiant::class); }

    /** Chambre d'internat occupée. */
    public function chambre(): BelongsTo     { return $this->belongsTo(Chambre::class); }

    /** Utilisateur ayant attribué le lit (directeur/superviseur). */
    public function attribuePar(): BelongsTo { return $this->belongsTo(User::class, 'attribue_par'); }
}
