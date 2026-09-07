<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** مكان الصلاة (المسجد / القاعة) */
class LieuPriere extends Model
{
    protected $table = 'lieux_priere';

    /** Champs assignables : identité (fr/ar), grille rangées × places pour générer les sièges. */
    protected $fillable = ['nom', 'nom_ar', 'description', 'nb_rangees', 'places_par_rangee', 'actif'];

    /** Drapeau actif en booléen. */
    protected $casts = ['actif' => 'boolean'];

    /** Places individuelles générées dans ce lieu (grille الصف / المكان). */
    public function places(): HasMany
    {
        return $this->hasMany(PlacePriere::class);
    }
}
