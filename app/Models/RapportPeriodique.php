<?php

namespace App\Models;

use App\Enums\NiveauMoyenne;
use App\Enums\NiveauPeriode;
use App\Enums\TypeRapportPeriodique;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Synthèse hebdomadaire / mensuelle consultable par le Directeur.
 * Générée automatiquement (updateOrCreate par période/type) à partir
 * des rapports journaliers agrégés : présences, pages, moyennes. */
class RapportPeriodique extends Model
{
    /** Champs assignables : période + type, compteurs de présence,
     * totaux de pages, moyennes par matière et appréciation finale. */
    protected $fillable = [
        'etudiant_id', 'groupe_id', 'type', 'date_debut', 'date_fin',
        'nb_seances', 'nb_presences', 'nb_absences', 'nb_retards',
        'total_pages_hifd', 'total_pages_murajaa',
        'moyenne_hifd', 'moyenne_murajaa', 'moyenne_comportement',
        'appreciation', 'genere_par',
    ];

    protected $table = 'rapports_periodiques';

    /** Type via enum, dates en Carbon, pages en décimal 2 et moyennes mappées sur des enums de niveaux. */
    protected $casts = [
        'type'                 => TypeRapportPeriodique::class,
        'date_debut'           => 'date',
        'date_fin'             => 'date',
        'total_pages_hifd'     => 'decimal:2',
        'total_pages_murajaa'  => 'decimal:2',
        'moyenne_hifd'         => NiveauMoyenne::class,
        'moyenne_murajaa'      => NiveauMoyenne::class,
        'moyenne_comportement' => NiveauPeriode::class,
    ];

    /** Étudiant concerné par la synthèse. */
    public function etudiant(): BelongsTo { return $this->belongsTo(Etudiant::class); }

    /** Groupe (حلقة) suivi pendant la période. */
    public function groupe(): BelongsTo   { return $this->belongsTo(Groupe::class); }

    /** Utilisateur ayant déclenché la génération du rapport. */
    public function generePar(): BelongsTo { return $this->belongsTo(User::class, 'genere_par'); }

    /** Taux de présence en pourcentage (ex. 85.7), null si aucune séance. */
    public function getTauxPresenceAttribute(): ?float
    {
        return $this->nb_seances > 0
            ? round($this->nb_presences / $this->nb_seances * 100, 1)
            : null;
    }
}
