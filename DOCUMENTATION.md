# Application de gestion d'une école coranique (Hifd Quran)

Application web Laravel 12 pour la gestion complète d'une école coranique
(مدرسة تحفيظ القرآن) : inscriptions, حلقات (groupes), التسميع (évaluation de la mémorisation),
rapports journaliers et périodiques, internat et gestion des professeurs.

- Interface **100 % arabe (RTL)** — la langue est imposée, il n'y a pas de sélecteur de langue.
- Code simplifié pour un développeur junior : chaque contrôleur est court, avec des vues Blade simples.

---

## 1. Rôles et droits

| Rôle | Rôle code | Responsabilité principale |
|---|---|---|
| Directeur | `directeur` | Vue globale, gestion des professeurs, des étudiants, des حلقات et des rapports périodiques |
| Superviseur des professeurs | `superviseur` | Tests d'admission, validation des inscriptions, peut aussi enseigner et saisir le تسميع |
| Garde général | `garde_general` | Pré-inscriptions, hébergement (lits/chambres), places de prière |
| Professeur | `professeur` | Saisie du rapport journalier (التسميع) pour ses étudiants uniquement |

> Règle importante : le **superviseur est aussi un professeur** — il peut encadrer des
> groupes et saisir des rapports comme un professeur, en plus de la gestion des tests d'admission.

### Comptes de démonstration (créés par le seeder)

Tous les comptes ont le mot de passe **`password`** :

- `directeur@madrasa.ma`  (3 comptes directeur : `directeur`, `directeur2`, `directeur3`)
- `superviseur@madrasa.ma` (3 comptes superviseur)
- `garde@madrasa.ma`      (3 comptes garde)
- `professeur@madrasa.ma` (3 comptes professeur)

---

## 2. Modules et fonctionnalités

### 2.1 Authentification
- Connexion (`/login`) et déconnexion.
- Mot de passe oublié : `/mot-de-passe-oublie` puis lien envoyé par e-mail (`/reinitialiser-mot-de-passe/{token}`).
- Profil utilisateur (`/profil`) et changement de mot de passe (`/profil/mot-de-passe`).

> En développement, les e-mails ne sont pas réellement envoyés : avec `MAIL_MAILER=log`,
> les liens de réinitialisation sont écrits dans le fichier `storage/logs/laravel.log`.

### 2.2 Tableau de bord personnalisé par rôle (`/`)
- **Directeur** : cartes statistiques, assiduité sur 30 jours, synthèse par حلقة,
  top 15 de progression de mémorisation, derniers rapports, répartition des statuts d'inscription.
- **Superviseur** : assiduité 30 jours, listes des tests en attente d'évaluation,
  synthèse par حلقة, superviseurs et enseignants actifs.
- **Professeur** : ses حلقات (effectifs, capacité, présences, moyenne du mois),
  étudiants non enregistrés aujourd'hui (avec lien direct vers la saisie), moyenne et rythme de ses étudiants.
- **Garde général** : occupation des lits par chambre, internes, dernières inscriptions,
  absences du jour, étudiants acceptés sans حلقة à affecter.

### 2.3 Étudiants (`/etudiants`)
- Pré-inscription (garde / directeur / superviseur) avec numéro d'inscription unique et documents.
- Fiche étudiant : identification (arabe + latin), statut, documents, groupe, hébergement.
- **Workflow d'inscription** : `preinscrit` → test d'admission → `valide` (ou `refuse` avec observation).
- Évaluation lors du test d'admission (`/etudiants/{etudiant}/evaluer`).
- Affectation à une حلقة (`/etudiants/{etudiant}/affecter`), y compris affectation multiple.
- Hébergement (lit + chambre) — garde seulement.
- Place de prière — garde seulement.

Statuts possibles : `preinscrit`, `en_test`, `valide`, `refuse`, `ajourne`, `abandon`.

### 2.4 Groupes / حلقات (`/groupes`)
- CRUD complet (création, lecture, modification, suppression).
- Professeur responsable, niveau, salle, horaires (début/fin), capacité limitée.
- Les étudiants valides d'un حلقة ne peuvent pas dépasser cette capacité.
- Consultation ouverte à tous les rôles ; gestion réservée aux professeurs, superviseurs et garde.

### 2.5 Professeurs (`/professeurs`)
- Géré **uniquement par le directeur** : création, modification, suppression.
- Activation / désactivation d'un compte (`toggle`) — un compte inactif ne peut plus se connecter.

### 2.6 Logements / chambres (`/logements`)
- CRUD réservé au garde général.
- Chaque chambre possède un numéro, un bâtiment, un étage et une capacité en lits.
- Les lits libres sont calculés automatiquement (1..capacité moins les lits occupés).

### 2.7 التسميع (saisie journalière) (`/tasmi`)
- Saisie réservée aux professeurs et superviseurs ; consultation pour le garde.
- Par étudiant et par séance (numéro de séance automatique max+1 du jour).
- Présence : présent / absent / retard / excusé.
- Lectures détaillées : **hifd jadid** (nouvelle mémorisation) et **hifd qadim** (révision),
  avec plage coranique (sourate début → ayah début → sourate fin → ayah fin) et note.
- La moyenne des lectures alimente la note globale du jour.
- Un professeur ne peut saisir que pour **ses** groupes ; le superviseur peut saisir pour tous.

### 2.8 Rapports journaliers (`/rapport-journaliers`)
- Liste avec recherche par nom, filtre par période.
- Saisie / modification / suppression (professeur), consultation pour tous.
- Détail du rapport avec les lectures réalisées.
- **Export PDF** du détail (`/rapport-journaliers/{rapport}/pdf`).
- **Export Excel** de la liste filtrée (`/rapport-journaliers/exporter`).

### 2.9 Rapports périodiques (`/rapport-periodiques`)
- Réservés au **directeur et superviseur**.
- Génération automatique sur une période (mois, etc.), par حلقة.
- Niveaux qualitatifs (lecture, comportement, mémorisation, révision, moyenne générale).
- **Export PDF** d'un rapport (`/rapport-periodiques/{rapport}/pdf`).
- **Export Excel** de la liste filtrée (`/rapport-periodiques/exporter`).

### 2.10 Référentiel coranique
- Les sourates (114) sont préchargées dans la base (seeder) pour définir les plages de lectures.

### 2.11 Obligations de مذكر (taches de mémorisation)
- Chaque tâche : étudiant, professeur, type de séance, plage coranique, nombre de pages,
  date d'échéance, consignes et statut.

---

## 3. Exports

| Format | Bibliothèque | Contenu |
|---|---|---|
| **PDF** | `barryvdh/laravel-dompdf` | Détail d'un rapport (journalier ou périodique) — document RTL autonome |
| **Excel** | `maatwebsite/excel` | Liste des rapports filtrée (journaliers ou périodiques) |

---

## 4. Prérequis techniques

- PHP **^8.2** (testé en **8.2.12**)
- Composer
- MySQL (base `hifd_quran_filament`)
- Extensions PHP : `pdo_mysql`, `mbstring`, `openssl`, `gd` (pour les images PDF), `zip` (pour Excel), `fileinfo`

Le projet utilise : Laravel 12, Blade, Bootstrap Icons, Filament 3 (passerelles/admin),
Livewire, `barryvdh/laravel-dompdf`, `maatwebsite/excel`, PHPUnit / Collision pour les tests.

---

## 5. Commandes pour installer et lancer l'application

### 5.1 Installation (une seule fois)

```bash
# 1. Installer les dépendances PHP
composer install

# 2. Créer le fichier d'environnement
copy .env.example .env        # sous Windows
# cp .env.example .env        # sous Linux/macOS

# 3. Générer la clé de chiffrement de l'application
php artisan key:generate

# 4. Configurer la base de données dans .env, ex. :
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=hifd_quran_filament
#    DB_USERNAME=root
#    DB_PASSWORD=

# 5. Créer la base puis exécuter les migrations + données de démonstration
#    (sourates, 12 comptes de test, groupes et étudiants de démo)
php artisan migrate --seed

# 6. (Recommandé) rendre le dossier storage public accessible
php artisan storage:link
```

### 5.2 Lancer l'application en développement

```bash
php artisan serve
```

Puis ouvrir : **http://127.0.0.1:8000**

> Note : lancer le serveur en arrière-plan blocque le terminal.
> Gardez-le dans un terminal dédié.

### 5.3 Tester l'application

```bash
php artisan test
```

Résultat attendu : **81 tests, 0 échec**. Les tests utilisent une base MySQL dédiée
(`hifd_quran_filament_test`), la base de développement reste intacte.

### 5.4 Commandes utiles quotidiennes

```bash
php artisan migrate                 # appliquer les migrations
php artisan migrate:fresh --seed    # reconstruire toute la base + données de démo
php artisan cache:clear             # vider le cache
php artisan config:clear            # recharger la configuration
php artisan route:list              # lister toutes les routes
php artisan tinker                  # console interactive (tests rapides)
```

---

## 6. Contrôle de santé

Une route simple renvoie l'état de l'application (utilisée par les moniteurs) :

```bash
curl http://127.0.0.1:8000/sante
# {"statut":"ok"}
```

---

## 7. Notes importantes

- **Langue** : l'application force l'arabe RTL (middleware `ForcerLangueArabe`).
- **E-mails** : en local (`MAIL_MAILER=log`), les e-mails de réinitialisation de mot de passe
  sont écrits dans `storage/logs/laravel.log`. Pour un envoi réel, configurez un serveur SMTP
  dans `.env` (`MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`).
- **Types de séance** : seuls `hifd_jadid` (nouvelle mémorisation) et `hifd_qadim` (révision)
  sont utilisés.
- **Route du tableau de bord** : `/` (nom `dashboard`) — il n'existe pas de `/dashboard`.