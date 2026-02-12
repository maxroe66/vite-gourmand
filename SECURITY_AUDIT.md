# 🔒 AUDIT DE SÉCURITÉ — Vite & Gourmand

**Date :** Audit réalisé le 2025  
**Scope :** Workspace complet (backend, frontend, config, CI/CD, Docker)  
**Statut :** Lecture seule — aucune modification apportée

---

## 📋 Table des Matières

1. [Résumé Exécutif](#résumé-exécutif)
2. [Vulnérabilités Critiques](#-vulnérabilités-critiques)
3. [Vulnérabilités Élevées](#-vulnérabilités-élevées)
4. [Vulnérabilités Moyennes](#-vulnérabilités-moyennes)
5. [Vulnérabilités Faibles](#-vulnérabilités-faibles)
6. [Bonnes Pratiques Déjà en Place](#-bonnes-pratiques-déjà-en-place)
7. [Recommandations Prioritaires](#-recommandations-prioritaires)

---

## Résumé Exécutif

| Sévérité | Nombre | Status |
|----------|--------|--------|
| 🔴 Critique | 3 (1 corrigée) | À corriger immédiatement |
| 🟠 Élevée | 5 | À corriger avant production |
| 🟡 Moyenne | 6 | À planifier |
| 🔵 Faible | 4 | Amélioration continue |

---

## 🔴 Vulnérabilités Critiques

### CRIT-01 : JWT stocké en localStorage (documentation / diagrammes de séquence) — ✅ CORRIGÉ

**Fichiers concernés :**
- `Docs/diagrammes/diagramme_sequences/sequence_01_inscription_connexion.md` (ligne ~121)
- Documentation mentionne : *"Stockage localStorage du token"*

**Risque :** Le stockage JWT en `localStorage` est vulnérable aux attaques **XSS**. Tout script malveillant injecté peut lire le token et usurper l'identité de l'utilisateur.

**Constat contradictoire :** La documentation `Docs/readme_cycle_de_vie/README_AUTH.md` (ligne 67) mentionne *"JWT en cookie HttpOnly"*, ce qui est la bonne pratique. Il y a donc une **incohérence** entre la documentation des séquences et la documentation Auth. Si le code utilise réellement `localStorage`, c'est critique. Si c'est bien un cookie HttpOnly, la documentation des séquences doit être corrigée.

**Résolution appliquée :**
- ✅ Le code utilise bien un cookie HttpOnly (confirmé par audit du code source)
- ✅ Suppression de `'token' => $token` du body JSON de la réponse login (`AuthController.php`)
- ✅ Nettoyage du code résiduel `localStorage.getItem('authToken')` dans `dashboard.js`
- ✅ Correction des 6 mentions erronées de "localStorage" dans la documentation (diagrammes de séquence, validation, doc technique)

**Impact :** Vol de session, usurpation d'identité  
**CVSS estimé :** 8.1 (Élevé) → **Résolu**

---

### CRIT-02 : Password Hashing — Incohérence Argon2 vs bcrypt

**Fichiers concernés :**
- `Docs/documentation_technique/DOCUMENTATION_TECHNIQUE.md` (ligne ~564) → mentionne `PASSWORD_ARGON2ID`
- `backend/database/sql/database_fixtures.sql` (ligne ~22) → utilise un hash bcrypt `$2y$10$...`

**Risque :** La documentation prétend utiliser Argon2ID, mais les fixtures utilisent bcrypt (`$2y$`). Si le code de production utilise réellement bcrypt, ce n'est pas critique en soi (bcrypt reste acceptable), mais l'incohérence documentaire pourrait masquer un problème de configuration.

**Action :** Vérifier quel algorithme est réellement utilisé dans `AuthService` ou le service d'inscription. Si c'est bcrypt, mettre à jour la documentation. Si c'est Argon2, mettre à jour les fixtures.

**Impact :** Potentiel affaiblissement du hashing si mauvaise configuration  
**CVSS estimé :** 7.5

---

### CRIT-03 : Mot de passe admin en clair dans le seed de production

**Fichier concerné :**
- `backend/database/sql/database_seed.sql` (ligne ~14) → *"Mot de passe initial admin : Jose@VG-Prod2025"*

**Risque :** Le mot de passe admin de production est documenté **en clair** dans un fichier versionné et public sur Git. Même s'il est hashé dans le SQL, le commentaire en clair permet à quiconque ayant accès au dépôt de connaître le mot de passe initial.

**Impact :** Compromission du compte administrateur si le mot de passe n'est pas changé après déploiement  
**CVSS estimé :** 9.1 (Critique)

---

## 🟠 Vulnérabilités Élevées

### HIGH-01 : Fichiers `.env` potentiellement exposés

**Fichiers concernés :**
- `.gitignore` → `.env` est ignoré ✅
- Mais `.env.test.example` n'est PAS ignoré et pourrait contenir des indices sur la structure des secrets
- `Docs/documentation_technique/DOCUMENTATION_DEPLOIEMENT.md` (ligne ~722) → Template `.env.example` avec structure complète exposée

**Risque :** La structure des variables d'environnement est documentée publiquement. Combinée avec d'autres informations, cela facilite la reconnaissance pour un attaquant.

**Impact :** Fuite d'information structurelle  
**CVSS estimé :** 6.5

---

### HIGH-02 : Absence de rate limiting documenté sur les routes d'authentification

**Fichiers concernés :**
- `backend/api/routes.auth.php` (référencé dans la documentation)
- `scripts/tests/test_backend.sh` (ligne ~31) → `rm -rf /tmp/vg_rate_limit/` — suggère un rate limiter basé sur le filesystem

**Risque :** Le rate limiter semble stocké dans `/tmp/vg_rate_limit/`. Un stockage filesystem pour le rate limiting est :
1. **Non persistant** entre redémarrages
2. **Non partagé** entre instances (scale-out Azure)
3. **Facilement contournable** si le dossier est supprimé

Les routes `/api/auth/login`, `/api/auth/register`, `/api/auth/forgot-password` sont des cibles privilégiées pour le brute-force.

**Impact :** Brute-force sur login, credential stuffing  
**CVSS estimé :** 7.3

---

### HIGH-03 : CSP autorise `'unsafe-inline'` pour les styles

**Fichier concerné :**
- `backend/tests/SecurityHeadersMiddlewareTest.php` (ligne ~25) → `'style_src' => ["'self'", "'unsafe-inline'", ...]`

**Risque :** `'unsafe-inline'` dans `style-src` affaiblit la Content Security Policy et peut être exploité dans certains scénarios d'injection (CSS injection, data exfiltration via CSS).

**Impact :** Contournement partiel de la CSP  
**CVSS estimé :** 5.3

---

### HIGH-04 : Version PHP 8.1 avec extensions potentiellement obsolètes

**Fichiers concernés :**
- `docker/php/Dockerfile.php` (ligne 2) → `FROM php:8.1-fpm`
- `Dockerfile.azure` (ligne 1) → `FROM php:8.1-apache`
- `.github/workflows/email-integration.yml` (ligne ~87) → `php-version: '8.1'`

**Risque :** PHP 8.1 a atteint sa fin de support de sécurité le **25 novembre 2024**. Aucun patch de sécurité n'est plus fourni.

**Impact :** Vulnérabilités PHP non corrigées  
**CVSS estimé :** 7.0

**Recommandation :** Migrer vers PHP 8.2 ou 8.3 (supportés activement).

---

### HIGH-05 : MongoDB 4.4 en CI — Version obsolète

**Fichier concerné :**
- `.github/workflows/test-backend.yml` (ligne ~37) → `image: mongo:4.4`

**Risque :** MongoDB 4.4 est en fin de vie (EOL février 2024). Plus aucun patch de sécurité.

**Impact :** Vulnérabilités MongoDB non corrigées dans l'environnement de test  
**CVSS estimé :** 5.0 (limité au CI, mais les tests pourraient ne pas détecter des incompatibilités avec des versions plus récentes en production)

---

## 🟡 Vulnérabilités Moyennes

### MED-01 : Commentaires SQL avec identifiants de test

**Fichier concerné :**
- `backend/database/sql/database_fixtures.sql` (ligne ~383) → Liste complète des emails et rôles de test

**Risque :** Même si c'est un fichier de test, les patterns d'email (`@vite-gourmand.fr`) et la structure des rôles donnent des informations utiles pour du social engineering ou des attaques ciblées.

---

### MED-02 : `frame-src: 'none'` mais pas de `X-Frame-Options: DENY` systématique

**Fichier concerné :**
- `backend/tests/SecurityHeadersMiddlewareTest.php` → CSP a `frame_src: 'none'`
- `Dockerfile.azure` (ligne ~37) → `X-Frame-Options "DENY"` configuré au niveau Apache

**Constat :** La protection est en place au niveau Apache (Dockerfile.azure), mais il faut s'assurer qu'elle est aussi active en développement local (Docker compose).

---

### MED-03 : Absence de validation de type MIME sur les uploads

**Fichiers concernés :**
- `backend/api/routes.php` → Route upload référencée
- Documentation (`readme_src.md` ligne ~43) mentionne : *"Upload : upload image (TODO sécurisation)"*

**Risque :** Sans validation stricte du type MIME (au-delà de l'extension), un attaquant pourrait uploader un fichier PHP déguisé en image et obtenir une exécution de code côté serveur (RCE).

**Impact :** Remote Code Execution potentielle  
**CVSS estimé :** 8.0 (remonté en critique si upload est accessible sans auth — mais la doc indique Auth+Role)

---

### MED-04 : Secrets GitHub Actions potentiellement insuffisamment protégés

**Fichier concerné :**
- `.github/workflows/deploy-azure.yml` (ligne ~129) → Utilise `${{ secrets.AZURE_MYSQL_HOST }}`, etc.

**Risque :** Les secrets sont correctement utilisés via `${{ secrets.* }}`, mais les commandes `mysql` avec `-p"$DB_PASS"` exposent le mot de passe dans la ligne de commande du processus (visible via `/proc` sur Linux).

**Recommandation :** Utiliser `MYSQL_PWD` comme variable d'environnement ou un fichier `.my.cnf` temporaire.

---

### MED-05 : Certificat SSL Azure copié dans l'image Docker

**Fichier concerné :**
- `Dockerfile.azure` (ligne ~24) → `COPY docker/certs/DigiCertGlobalRootCA.crt.pem ...`

**Risque :** Le certificat CA DigiCert est public, donc pas de fuite de secret. Cependant, le dossier `docker/certs/` est dans `.gitignore`, ce qui signifie que si quelqu'un place des certificats privés dans ce dossier, ils ne seront pas versionnés — c'est bon. Mais le `COPY` dans le Dockerfile implique qu'ils doivent être présents au build.

**Impact :** Faible — point d'attention pour le workflow de build.

---

### MED-06 : SameSite=None sur les cookies

**Fichier concerné :**
- `Docs/readme_cycle_de_vie/README_AUTH.md` (ligne 67) → *"SameSite=None + Secure en HTTPS"*

**Risque :** `SameSite=None` est nécessaire pour le cross-site mais augmente la surface d'attaque CSRF. La protection CSRF (Double-Submit Cookie) est en place (`README_CSRF.md`), ce qui atténue le risque, mais c'est un point à surveiller.

---

## 🔵 Vulnérabilités Faibles

### LOW-01 : Version de jQuery/CDN non épinglée dans la CSP

**Fichier concerné :**
- `backend/tests/SecurityHeadersMiddlewareTest.php` → `'script_src' => ["'self'", 'https://cdn.jsdelivr.net']`

**Risque :** Autoriser tout `cdn.jsdelivr.net` permet de charger n'importe quelle bibliothèque depuis ce CDN, y compris des versions vulnérables. Il serait préférable de restreindre à des chemins spécifiques ou d'utiliser des hashes/nonces.

---

### LOW-02 : Logs potentiellement verbeux en production

**Fichiers concernés :**
- `Docs/readme_configurations/DEBUG_MONGODB_AZURE.md` (ligne ~210) → Logs verbose pour debug
- Documentation mentionne de désactiver après résolution

**Risque :** Des logs trop verbose en production peuvent exposer des informations sensibles (URI de connexion, structure interne).

---

### LOW-03 : `display_errors=1` dans le serveur de test

**Fichier concerné :**
- `scripts/tests/test_backend.sh` (ligne ~46) → `php -d display_errors=1`

**Risque :** Limité à l'environnement de test, mais si cette configuration fuite en production, elle exposerait des traces de pile et des chemins de fichiers.

---

### LOW-04 : Absence de Content-Type sniffing prevention sur toutes les réponses API

**Constat :** `X-Content-Type-Options: nosniff` est configuré au niveau Apache (Dockerfile.azure), mais il faut s'assurer qu'il est aussi envoyé pour les réponses JSON de l'API en développement.

---

## ✅ Bonnes Pratiques Déjà en Place

| Pratique | Status | Détail |
|----------|--------|--------|
| **JWT en cookie HttpOnly** | ✅ | Documenté dans README_AUTH.md |
| **Protection CSRF (Double-Submit)** | ✅ | Implémenté (README_CSRF.md) |
| **CORS configuré** | ✅ | CorsMiddleware en place |
| **Prepared Statements SQL** | ✅ | PDO avec paramètres liés |
| **Validation côté serveur** | ✅ | Validators dédiés |
| **Rôles via middleware** | ✅ | RoleMiddleware (EMPLOYE/ADMIN) |
| **HTTPS + HSTS** | ✅ | Headers dans Dockerfile.azure |
| **Mots de passe hashés** | ✅ | bcrypt au minimum |
| **`.env` dans `.gitignore`** | ✅ | Secrets non versionnés |
| **CSP configurée** | ✅ | SecurityHeadersMiddleware |
| **Fallback MongoDB → MySQL** | ✅ | AVIS_FALLBACK pour résilience |
| **Rotation CSRF après login** | ✅ | Documenté dans README_CSRF.md |
| **SSL/TLS Azure MySQL** | ✅ | `--ssl-mode=REQUIRED` |
| **Permissions Docker non-root** | ✅ | Utilisateur dédié dans Dockerfile.php |
| **Secrets CI/CD via GitHub Secrets** | ✅ | Pas de secrets en dur dans les workflows |
| **Upload protégé par Auth+Role** | ✅ | AuthMiddleware + RoleMiddleware |
| **JWT_SECRET dynamique en test** | ✅ | `openssl rand -hex 32` dans test_backend.sh |

---

## 🎯 Recommandations Prioritaires

### Priorité 1 — Immédiat (avant production)

1. **Supprimer le mot de passe admin en clair** de `database_seed.sql` (commentaire ligne 14). Utiliser une variable d'environnement ou un prompt interactif au premier déploiement.
2. **Mettre à jour PHP vers 8.2+** dans tous les Dockerfiles et workflows CI.
3. **Clarifier le stockage JWT** : vérifier le code source et harmoniser la documentation (localStorage vs HttpOnly cookie).
4. **Sécuriser les uploads** : ajouter une validation MIME stricte côté serveur (magic bytes, pas seulement l'extension).

### Priorité 2 — Court terme

5. **Migrer le rate limiter** vers Redis ou une solution partagée (au lieu de `/tmp`).
6. **Mettre à jour MongoDB** vers 6.0+ dans le CI.
7. **Épingler les versions CDN** dans la CSP ou utiliser des SRI (Subresource Integrity).
8. **Protéger le mot de passe MySQL** dans les commandes CI (`MYSQL_PWD` au lieu de `-p`).

### Priorité 3 — Amélioration continue

9. **Supprimer `'unsafe-inline'`** de `style-src` dans la CSP (utiliser des nonces ou des hashes).
10. **Ajouter des headers de sécurité** en développement local (pas seulement en production Azure).
11. **Mettre en place un scan de dépendances** automatique (`composer audit`, Dependabot).
12. **Documenter une politique de rotation des secrets** (JWT_SECRET, mots de passe Azure).

---

## 📊 Score Global de Sécurité

| Catégorie | Score |
|-----------|-------|
| Authentification & Autorisation | 7/10 |
| Protection des données | 7/10 |
| Configuration serveur | 6/10 |
| Gestion des secrets | 6/10 |
| Headers de sécurité | 8/10 |
| CI/CD Security | 7/10 |
| Dépendances | 5/10 |
| **Score Global** | **6.6/10** |

> **Verdict :** L'architecture de sécurité est **globalement solide** avec de bonnes pratiques en place (CSRF, CORS, JWT HttpOnly, CSP, HSTS). Les points critiques identifiés (mot de passe admin en clair, PHP EOL, incohérence documentation JWT) doivent être adressés avant une mise en production définitive.

---

*Audit réalisé par analyse statique du workspace. Un audit dynamique (pentest) est recommandé en complément.*