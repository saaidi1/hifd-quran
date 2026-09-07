<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Place attribuée à l'étudiant dans le lieu de prière (الصف ورقم المكان). */
class PlacePriere extends Model
{
    protected $table = 'places_priere';

    /** Champs assignables : étudiant, lieu + coordonnées (rangee, numero_place = matricule),
     * période de validité et auteur de l'attribution. */
    protected $fillable = [
        'etudiant_id', 'lieu_priere_id', 'rangee', 'numero_place',
        'date_debut', 'date_fin', 'attribue_par', 'actif',
    ];

    /** Dates en Carbon, drapeau actif en booléen. */
    protected $casts = ['date_debut' => 'date', 'date_fin' => 'date', 'actif' => 'boolean'];

    /** Étudiant à qui la place est attribuée (une seule place active via Etudiant::placePriere). */
    public function etudiant(): BelongsTo   { return $this->belongsTo(Etudiant::class); }

    /** Lieu de prière (مسجد / قاعة) contenant cette place. */
    public function lieuPriere(): BelongsTo { return $this->belongsTo(LieuPriere::class); }
}
