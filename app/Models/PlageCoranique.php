<?php

namespace App\Models;

/**
 * Trait partagé par tout modèle décrivant une plage du Coran :
 * « من الآية (الم) من سورة البقرة إلى الآية (وبالآخرة هم يوقنون) ».
 * Utilisé par LigneRapport et TacheMemorisation (sourate/ayah début-fin).
 */
trait PlageCoranique
{
    /** Sourate de début de la plage. */
    public function sourateDebut() { return $this->belongsTo(Sourate::class, 'sourate_debut_id'); }

    /** Sourate de fin de la plage. */
    public function sourateFin()   { return $this->belongsTo(Sourate::class, 'sourate_fin_id'); }

    /** Nombre de versets couverts par la plage. */
    public function nombreVersets(): int
    {
        $debut = $this->sourateDebut->positionGlobale($this->ayah_debut);
        $fin   = $this->sourateFin->positionGlobale($this->ayah_fin);

        return max(0, $fin - $debut + 1);
    }

    /** Libellé arabe lisible de la plage. */
    public function libellePlage(): string
    {
        $d = $this->sourateDebut;
        $f = $this->sourateFin;

        if ($d->id === $f->id) {
            return "سورة {$d->nom_ar} : من الآية {$this->ayah_debut} إلى الآية {$this->ayah_fin}";
        }

        return "من سورة {$d->nom_ar} الآية {$this->ayah_debut} إلى سورة {$f->nom_ar} الآية {$this->ayah_fin}";
    }

    /** Validation : la fin ne doit pas précéder le début. */
    public function plageEstValide(): bool
    {
        return $this->sourateFin->positionGlobale($this->ayah_fin)
             >= $this->sourateDebut->positionGlobale($this->ayah_debut);
    }
}
