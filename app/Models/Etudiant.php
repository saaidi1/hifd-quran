<?php

namespace App\Models;

use App\Enums\StatutInscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Étudiant (طالب) : entité centrale de l'école coranique.
 * Porte le flux complet d'admission : pré-inscription (garde) → test (superviseur)
 * → validation (directeur) → affectation à un groupe (حلقة), puis suivi
 * (تسميع, rapports, comportement) et services internes (lit + place de prière).
 */
class Etudiant extends Model
{
    /** Champs assignables du dossier d'admission : identité (fr/ar), naissance,
     * documents justificatifs, tuteur légal, niveau initial de hifd et workflow. */
    protected $fillable = [
        'matricule', 'nom', 'prenom', 'nom_ar', 'prenom_ar',
        'date_naissance', 'lieu_naissance', 'sexe', 'cin', 'photo',
        'extrait_naissance', 'attestation_scolaire', 'autre_document',
        'adresse', 'ville', 'telephone',
        'tuteur_nom', 'tuteur_lien', 'tuteur_telephone',
        'niveau_scolaire', 'hifd_initial_hizb',
        'statut', 'preinscrit_par', 'date_preinscription',
        'valide_par', 'date_validation', 'motif_refus',
        'groupe_id', 'interne', 'actif',
    ];

    /** Statut piloté par l'enum StatutInscription (workflow d'admission). */
    protected $casts = [
        'statut'              => StatutInscription::class,
        'date_naissance'      => 'date',
        'date_preinscription' => 'date',
        'date_validation'     => 'date',
        'hifd_initial_hizb'   => 'decimal:2',
        'interne'             => 'boolean',
        'actif'               => 'boolean',
    ];

    /* ---------------- Relations ---------------- */

    /** Groupe (حلقة) actuellement fréquenté. */
    public function groupe(): BelongsTo          { return $this->belongsTo(Groupe::class); }

    /** Garde général ayant enregistré la pré-inscription. */
    public function preinscritPar(): BelongsTo   { return $this->belongsTo(User::class, 'preinscrit_par'); }

    /** Directeur ayant validé (ou refusé) l'admission. */
    public function validePar(): BelongsTo       { return $this->belongsTo(User::class, 'valide_par'); }

    /** Tests de niveau passés avant validation de l'inscription. */
    public function evaluations(): HasMany       { return $this->hasMany(EvaluationInscription::class); }

    /** Historique des affectations aux groupes (حلقات). */
    public function affectations(): HasMany      { return $this->hasMany(Affectation::class); }

    /** واجب مقرر : tâches de mémorisation assignées au préalable par le professeur. */
    public function taches(): HasMany            { return $this->hasMany(TacheMemorisation::class); }

    /** Rapports journaliers de تسميع (une ligne par jour et séance). */
    public function rapportsJournaliers(): HasMany { return $this->hasMany(RapportJournalier::class); }

    /** Synthèses hebdomadaires/mensuelles générées. */
    public function rapportsPeriodiques(): HasMany { return $this->hasMany(RapportPeriodique::class); }

    /** Incidents, encouragements et sanctions (السلوك). */
    public function comportements(): HasMany     { return $this->hasMany(Comportement::class); }

    /** الإيواء ورقم السرير */
    public function hebergement(): HasOne
    {
        return $this->hasOne(Hebergement::class)->where('actif', true);
    }

    /** مكان الصلاة */
    public function placePriere(): HasOne
    {
        return $this->hasOne(PlacePriere::class)->where('actif', true);
    }

    /* ---------------- Règles métier ---------------- */

    /** Vrai si le statut courant autorise l'affectation à un groupe. */
    public function peutEtreAffecte(): bool
    {
        return $this->statut->peutEtreAffecte();
    }

    /** Dernier test de niveau réalisé (par date décroissante). */
    public function derniereEvaluation()
    {
        return $this->evaluations()->latest('date_test')->first();
    }

    /* ---------------- Scopes ---------------- */

    /** Étudiants dont l'admission est validée par le directeur. */
    public function scopeValides($q)      { return $q->where('statut', StatutInscription::VALIDE); }

    /** Pré-inscrits ou en attente de test (dossier en cours). */
    public function scopeEnAttente($q)    { return $q->whereIn('statut', [StatutInscription::PREINSCRIT, StatutInscription::EN_TEST]); }

    /** Étudiants pas encore rattachés à une حلقة. */
    public function scopeSansGroupe($q)   { return $q->whereNull('groupe_id'); }

    /** Internes hébergés sur place. */
    public function scopeInternes($q)     { return $q->where('interne', true); }

    /** Recherche par nom/prénom (arabe et latin) ou matricule, dans un seul where(...orWhere...). */
    public function scopeRecherche($q, string $terme)
    {
        return $q->where(function ($sousRequete) use ($terme) {
            $sousRequete->where('nom_ar', 'like', "%{$terme}%")
                ->orWhere('prenom_ar', 'like', "%{$terme}%")
                ->orWhere('nom', 'like', "%{$terme}%")
                ->orWhere('prenom', 'like', "%{$terme}%")
                ->orWhere('matricule', 'like', "%{$terme}%");
        });
    }

    /** Nom complet latin « prénom nom ». */
    public function getNomCompletAttribute(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    /** Nom complet arabe « الاسم اللقب ». */
    public function getNomCompletArAttribute(): string
    {
        return trim($this->prenom_ar . ' ' . $this->nom_ar);
    }

    /** Numéro d'inscription en entier : identique au numéro de lit et de place de prière. */
    public function numeroInscription(): int
    {
        return (int) ltrim($this->matricule, '0');
    }

    /** Fichiers de documents (extrait de naissance, attestation, photo, autre). */
    public function documents(): array
    {
        return array_filter([
            'extrait_naissance'    => $this->extrait_naissance,
            'attestation_scolaire' => $this->attestation_scolaire,
            'photo'                => $this->photo,
            'autre_document'       => $this->autre_document,
        ]);
    }

    /** Vrai si les 3 documents obligatoires (extrait, attestation, photo) sont présents. */
    public function documentsComplets(): bool
    {
        return $this->extrait_naissance
            && $this->attestation_scolaire
            && $this->photo;
    }
}
