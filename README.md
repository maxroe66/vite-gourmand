# Vite & Gourmand

Application web de gestion de menus, commandes et avis (MySQL + MongoDB).

[![Tests backend automatisés](https://github.com/<OWNER>/<REPO>/actions/workflows/test-backend.yml/badge.svg?branch=develop)](https://github.com/<OWNER>/<REPO>/actions/workflows/test-backend.yml?query=branch%3Adevelop)

---

## Vue d’ensemble

Vite & Gourmand est une application web permettant :
- aux visiteurs de consulter les menus et s’inscrire,
- aux utilisateurs de commander et laisser un avis,
- aux employés de gérer les menus/commandes/avis,
- aux administrateurs de consulter des statistiques.

---

## Démarrage (DEV) — base réelle

Prérequis : **Docker + Docker Compose**.

bash
docker compose up -d

- Application : http://localhost:8000  
- phpMyAdmin : http://localhost:8081  
- Mongo Express : http://localhost:8082  

**En dev, l’app utilise :**
- MySQL (dev) : `vite_gourmand` sur `3306`
- MongoDB (dev) : `vite_gourmand` sur `27017`

---

## Tests backend (DB de test + API)

Lance tout (reset DB test + PHPUnit + Newman) :
bash
./scripts/test_backend.sh

**En test, l’app utilise :**
- MySQL (test) : `vite_gourmand_test` sur `3307`
- MongoDB (test) : `vite_gourmand_test` sur `27018`

---

## Configuration

- `.env.example` : template (à copier vers `.env`)
- `.env` : configuration DEV (base réelle)
- `.env.test` : configuration TEST (base test)

&gt; Ne versionne pas les fichiers `.env` contenant des secrets.

---

## Structure (résumé)

- Entrée web : `public/index.php`
- Backend : `backend/`
- SQL : `backend/database/sql/`
- Mongo setup : `backend/database/mongoDB/`
- Tests : `backend/tests/`

---

## Documentation

- Docs techniques : `Docs/documentation_technique/`
- Diagrammes : `Docs/diagrammes/`

---

## Licence

Projet réalisé dans le cadre d’un projet de formation / démonstration.
