# Hifd Quran — Gestion d'une école coranique

Application web Laravel 12 pour la gestion complète d'une école coranique
(مدرسة تحفيظ القرآن) : inscriptions, حلقات (groupes), التسميع (évaluation de la mémorisation),
rapports journaliers et périodiques, internat et gestion des professeurs.

- Interface **100 % arabe (RTL)**.
- Code simplifié, adapté à un développeur junior.
- Exports **PDF** et **Excel** intégrés.

## Documentation complète

Voir [**DOCUMENTATION.md**](DOCUMENTATION.md) pour :
- les rôles et droits (directeur, superviseur, garde général, professeur) ;
- tous les modules et fonctionnalités ;
- les commandes d'installation et de lancement ;
- les comptes de démonstration.

## Démarrage rapide

```bash
composer install
copy .env.example .env        # Windows — puis configurez DB_DATABASE, DB_USERNAME...
php artisan key:generate
php artisan migrate --seed
php artisan serve             # http://127.0.0.1:8000
```

## Tests

```bash
php artisan test              # 81 tests, 0 échec
```

## Exigences

- PHP ^8.2
- Composer
- MySQL

## Licence

MIT