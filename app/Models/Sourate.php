<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Table de référence des 114 sourates (6236 versets).
 * `ayah_cumul` = numéro global du 1er verset de la sourate - 1,
 * ce qui permet de calculer une distance en versets entre deux positions.
 */
class Sourate extends Model
{
    /** Table de référence statique : pas d'horodatage. */
    public $timestamps = false;

    /** Champs de référence : numéro coranique, noms (ar/fr), nombre de versets,
     * cumul global et position dans le plan de lecture (juz). */
    protected $fillable = ['numero', 'nom_ar', 'nom_fr', 'nb_ayat', 'ayah_cumul', 'type', 'juz_debut'];

    /** Numéro global du verset dans tout le Coran = cumul de la sourate + ayah
     * (sert à mesurer des distances entre deux plages). */
    public function positionGlobale(int $ayah): int
    {
        return $this->ayah_cumul + $ayah;
    }

    /** Libellé affichable « البقرة (La Vache) ». */
    public function getNomAttribute(): string
    {
        return $this->nom_ar . ' (' . $this->nom_fr . ')';
    }
}
