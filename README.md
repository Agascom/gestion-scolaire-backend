# Backend API — Gestion de Complexe Scolaire (Gabon)

Backend Laravel 11 (API REST + administration) du logiciel de gestion de complexe scolaire pour le Gabon (maternelle → terminale).

## Stack

- **Laravel 11** / PHP 8.2+
- **Base de données** : SQLite en développement local, **MySQL** en production (voir `.env`)
- **Authentification** : Laravel Sanctum (jetons)
- **Rôles & permissions** : Spatie Laravel Permission
- **Multi-écoles** : isolation par `school_id` (Global Scope `TenantScope`)

## Comptes de démonstration (seed)

| Rôle | Email | Mot de passe |
|---|---|---|
| SuperAdmin | `admin@complexe.ga` | `password` |
| Admin école | `direction@complexe.ga` | `password` |

## Initialisation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

> Par défaut le projet tourne sur **SQLite** (fichier `database/database.sqlite`) pour un démarrage immédiat.
> Pour basculer sur **MySQL** : créer la base `gestion_scolaire`, puis dans `.env` décommenter le bloc MySQL
> et commenter `DB_CONNECTION=sqlite`. Relancer ensuite `php artisan migrate --seed`.

## Multi-écoles (principe)

- Toute table métier possède une colonne `school_id` indexée.
- Les modèles concernés utilisent le trait `HasTenant` (`app/Models/Traits/HasTenant.php`) qui :
  1. applique un **Global Scope** filtrant automatiquement par `school_id` du user connecté ;
  2. **pré-remplit** `school_id` à la création.
- Le superadmin accède à toutes les écoles ; toute autre personne uniquement à la sienne.

## Routes API v1 (`/api/v1`)

| Méthode | Route | Description | Auth |
|---|---|---|---|
| POST | `/auth/login` | Connexion → token Sanctum | Public |
| POST | `/auth/logout` | Révocation du token | Bearer |
| GET | `/auth/user` | Utilisateur connecté + école + rôles | Bearer |
| GET | `/ecoles` | Écoles accessibles | Bearer |
| GET | `/cycles` | Cycles (Maternelle→Lycée) | Bearer |
| GET | `/niveaux` | Niveaux / cycles | Bearer |
| GET | `/matieres` | Matières de référence | Bearer |

Toutes les réponses suivent le format : `{ "success": bool, "data": ..., "message": "..." }`

## Structures de données principales (migrations)

- **schools** : écoles (multi-écoles)
- **annees_academiques** + **trimestres** : années scolaires (ex : 2025-2026) et périodes
- **cycles / niveaux / matieres** : référentiel gabonais
- **classes / salles** : classes par année et salles
- **eleves / parents** : élèves (soft delete) et tuteurs
- **enseignants** : personnel enseignant (soft delete)
- **matiere_classe** : affectation matière ↔ classe ↔ enseignant (coefficient)
- **evaluations / notes** : interrogation, devoir, composition, examen + notes /20
- **classe_eleve** : inscription d'un élève dans une classe pour une année

## Prochaines étapes (modules à implémenter)

Modules prévus (voir `SPECIFICATIONS_FONCTIONNELLES.md`) :
inscriptions/transferts, fins d'année (clôture + archivage), finances (frais, scolarité,
salaire, achats/ventes), trimestres (conseil de classe), bulletins PDF, emplois du temps,
matériel, utilisateurs/audit. Le schéma de données de base est déjà en place pour l'ensemble.