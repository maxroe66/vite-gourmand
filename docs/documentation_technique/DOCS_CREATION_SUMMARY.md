# Résumé des Documentations — Vite & Gourmand

**Date de mise à jour :** 18 février 2026  
**Statut :** ✅ Documentation complète

---

## Documents techniques

### 1. DOCUMENTATION_TECHNIQUE.md

**Localisation :** `docs/documentation_technique/DOCUMENTATION_TECHNIQUE.md`  

| Section | Contenu |
|---------|---------|
| Réflexions technologiques | PHP 8 vanilla (justification vs frameworks), MySQL 8, MongoDB 4.4, JS vanilla |
| Architecture générale | MVC / Service / Repository, PHP-DI, schéma des couches |
| Backend détaillé | 11 Controllers, 11 Services, 12 Repositories, 6 Middlewares, 10 Validators, 7 Models |
| Frontend détaillé | 10 pages HTML, CSS @layer, 41 fichiers JS, services API |
| Modèle de données | 20 tables MySQL, 2 collections MongoDB, relations, vues, triggers |
| API REST | 46 endpoints documentés (Auth, Menus, Plats, Commandes, Avis, Admin, etc.) |
| Sécurité | JWT HS256 cookie HttpOnly, CSRF Double Submit Cookie, Argon2ID, CSP, Rate Limiting |
| Flux métier | Cycle commande (8 états), inscription/connexion, avis, matériel |
| Tests | 32 fichiers PHPUnit, 20 fichiers Vitest, 10 collections Postman |
| Performance | Indexation SQL, requêtes optimisées |

---

### 2. DOCUMENTATION_DEPLOIEMENT.md

**Localisation :** `docs/documentation_technique/DOCUMENTATION_DEPLOIEMENT.md`  

| Section | Contenu |
|---------|---------|
| Prérequis | Docker, Docker Compose, ports |
| Architecture Docker | 8 services (PHP-FPM, Apache, MySQL, MySQL-test, MongoDB, MongoDB-test, phpMyAdmin, Mongo Express) |
| Configuration | Variables d'environnement (.env), volumes, réseaux |
| Déploiement Azure | App Service, Blob Storage, Cosmos DB |
| CI/CD | 4 workflows GitHub Actions (backend, frontend, deploy, security) |
| SSL | Certificats auto-signés (dev), Let's Encrypt (prod) |
| Troubleshooting | Problèmes courants + commandes de diagnostic |

---

### 3. MANUEL_UTILISATION.md

**Localisation :** `docs/documentation_technique/MANUEL_UTILISATION.md`  

| Section | Contenu |
|---------|---------|
| Accès | URL locale + comptes de test (admin, employé, utilisateur) |
| Parcours visiteur | Accueil, consultation menus, filtres, détail menu |
| Parcours utilisateur | Inscription, connexion, passer commande, suivi, avis, profil |
| Parcours employé | Dashboard, gestion menus/plats/commandes/avis/horaires/matériel |
| Parcours administrateur | Gestion employés, statistiques |
| Mot de passe oublié | Processus de réinitialisation |

---

### 4. GESTION_PROJET.md

**Localisation :** `docs/documentation_technique/GESTION_PROJET.md`  

| Section | Contenu |
|---------|---------|
| Méthodologie | Kanban adapté (Trello), sprints personnels |
| Chronologie | 7 phases (novembre 2025 → février 2026) |
| Organisation | Colonnes Trello, critères de validation |
| Gestion Git | Branching (main/develop/feature), conventions de commits |
| Stratégie de tests | Pyramide de tests (unitaires, intégration, E2E) |
| Difficultés rencontrées | JWT/Session, MongoDB sync, CSRF, Google Maps fallback |
| Bilan | Objectifs atteints, compétences acquises |

---

## Diagrammes mis à jour

| Diagramme | Fichier | Statut |
|-----------|---------|--------|
| **UML Classes** | `docs/diagrammes/diagramme_classes_uml/diagramme_classes_uml.md` | ✅ Réécrit — 68 classes (Mermaid) |
| **Cas d'utilisation** | `docs/diagrammes/diagramme_cas_utilisation/diagramme_cas_utilisation.md` | ✅ Mis à jour — JWT cookie HttpOnly, CSRF |
| **Séquence 01** | `docs/diagrammes/diagramme_sequences/sequence_01_inscription_connexion.md` | ✅ Mis à jour — Argon2ID, AuthService, CsrfService |
| **Index séquences** | `docs/diagrammes/diagramme_sequences/SEQUENCES_INDEX.md` | ✅ Mis à jour |
| **MLD** | `docs/diagrammes/diagrammes_MCD_MLD/diagramme_mld/diagramme_mld_simplifié_pour_compatibilité.md` | ✅ Mis à jour — 20 tables (+ MATERIEL, MENU_MATERIEL, COMMANDE_MATERIEL) |
| **MCD** | `docs/diagrammes/diagrammes_MCD_MLD/diagramme_mcd/diagramme_mcd.md` | ✅ Conforme (12 entités, 38 règles métier) |
| **Validation** | `docs/diagrammes/VALIDATION_DIAGRAMMES.md` | ✅ Mis à jour — compteurs corrigés |
| **README diagrammes** | `docs/diagrammes/README_DIAGRAMMES.md` | ✅ Mis à jour |

---

## Statistiques globales

| Métrique | Valeur |
|----------|--------|
| Documents techniques | 4 fichiers Markdown |
| Diagrammes | 8 fichiers mis à jour |
| Exemples de code | 60+ snippets |
| Tableaux | 30+ tables |
| Endpoints documentés | 46 |
| Classes UML | 68 |
| Tables SQL documentées | 20 |

---

**Dernière mise à jour :** 18 février 2026
