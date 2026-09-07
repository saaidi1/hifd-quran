<?php

namespace App\Models;

use App\Enums\RoleUtilisateur;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Utilisateur de l'application (directeur, المشرف على الأساتذة, أستاذ, garde).
 * Règles de rôles : le superviseur est aussi un professeur — d'où deux scopes :
 * scopeProfesseurs() = rôle PROFESSEUR strict ; scopeEncadrants() = professeurs + superviseurs.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** Champs assignables : identité (fr/ar), rôle via enum, spécialité éventuelle. */
    protected $fillable = [
        'name', 'nom', 'prenom', 'nom_ar', 'email', 'password',
        'role', 'telephone', 'specialite', 'actif',
    ];

    /** Attributs sensibles exclus de la sérialisation. */
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => RoleUtilisateur::class,
            'actif'             => 'boolean',
        ];
    }

    /* ---------------- Nom affiché ---------------- */

    /** Nom affiché dans l'interface (arabe si disponible). */
    public function getFilamentName(): string
    {
        return $this->nom_ar ?: $this->nom_complet;
    }

    /* ---------------- Relations ---------------- */

    /** Groupes (حلقات) encadrés par ce professeur. */
    public function groupes(): HasMany
    {
        return $this->hasMany(Groupe::class, 'professeur_id');
    }

    /** Rapports journaliers de تسميع saisis par ce professeur. */
    public function rapportsJournaliers(): HasMany
    {
        return $this->hasMany(RapportJournalier::class, 'professeur_id');
    }

    /** Tests d'admission conduits par ce superviseur. */
    public function evaluationsInscription(): HasMany
    {
        return $this->hasMany(EvaluationInscription::class, 'superviseur_id');
    }

    /** Étudiants pré-inscrits par ce garde général. */
    public function preinscriptions(): HasMany
    {
        return $this->hasMany(Etudiant::class, 'preinscrit_par');
    }

    /** Tous les étudiants des groupes du professeur. */
    public function etudiants()
    {
        return Etudiant::whereIn('groupe_id', $this->groupes()->select('id'));
    }

    /* ---------------- Helpers de rôle ---------------- */

    /** Vrai si l'utilisateur est directeur. */
    public function estDirecteur(): bool   { return $this->role === RoleUtilisateur::DIRECTEUR; }

    /** Vrai si l'utilisateur supervise les professeurs (et peut aussi enseigner). */
    public function estSuperviseur(): bool { return $this->role === RoleUtilisateur::SUPERVISEUR; }

    /** Vrai si l'utilisateur est garde général (pré-inscriptions). */
    public function estGarde(): bool       { return $this->role === RoleUtilisateur::GARDE; }

    /** Vrai si l'utilisateur est strictement professeur. */
    public function estProfesseur(): bool  { return $this->role === RoleUtilisateur::PROFESSEUR; }

    /** Vrai si le rôle courant fait partie des rôles passés en paramètre. */
    public function aRole(RoleUtilisateur ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /** Nom complet latin « prénom nom ». */
    public function getNomCompletAttribute(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    /** Professeurs au sens strict (rôle PROFESSEUR uniquement). */
    public function scopeProfesseurs($query)
    {
        return $query->where('role', RoleUtilisateur::PROFESSEUR);
    }

    /** Le superviseur est aussi un أستاذ : il peut encadrer des حصص et des مجموعات. */
    public function scopeEncadrants($query)
    {
        return $query->whereIn('role', [RoleUtilisateur::PROFESSEUR, RoleUtilisateur::SUPERVISEUR]);
    }
}
