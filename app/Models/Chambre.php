<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** الغرفة (داخلية الإيواء) */
class Chambre extends Model
{
    /** Champs assignables : localisation (numéro, bâtiment, étage), capacité en lits et responsable. */
    protected $fillable = ['numero', 'batiment', 'etage', 'capacite', 'responsable_id', 'actif'];

    /** Capacité en entier, drapeau actif en booléen. */
    protected $casts = ['actif' => 'boolean', 'capacite' => 'integer'];

    /** Lits actuellement occupés par les internes. */
    public function hebergements(): HasMany
    {
        return $this->hasMany(Hebergement::class)->where('actif', true);
    }

    /** Numéros de lits encore libres = 1..capacité moins les numéros occupés. */
    public function litsLibres(): array
    {
        $occupes = $this->hebergements()->pluck('numero_lit')->all();

        return array_values(array_diff(range(1, $this->capacite), $occupes));
    }
}
