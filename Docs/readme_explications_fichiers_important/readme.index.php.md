
# 📘 Documentation Technique – Point d’Entrée `public/index.php`

---

## 🎯 Objectif du document

Ce document présente au jury ECF le fonctionnement complet de `public/index.php`, point d'entrée principal de l’application web PHP.

**Il détaille :**

- Son rôle central dans l’architecture (Front Controller)
- Les étapes techniques de traitement d'une requête
- Le chargement de l’environnement et de la configuration
- La gestion de la sécurité (HTTPS, HSTS, erreurs)
- L’initialisation de l’injection de dépendances (DI)
- Le routage des API et des pages statiques
- La gestion globale des erreurs

> Ce fichier est le cœur de la mécanique HTTP de l’application.

---

## 🧭 1. Rôle principal du `index.php`

La logique repose sur un **Front Controller**, un pattern architectural commun aux frameworks modernes (**Symfony, Laravel, Slim**).

➡️ Toutes les requêtes HTTP passent par ce fichier, qui :

- Initialise l’environnement
- Charge les dépendances
- Configure les options de sécurité
- Prépare la requête
- Appelle le routeur
- Renvoie la réponse au client

Ce modèle centralisé garantit une cohérence, une sécurité et une maintenabilité maximales.

---

## 🧱 2. Chargement de l’environnement et des dépendances

### ✔️ 2.1 Autoload Composer (PSR‑4)

Le fichier charge automatiquement les classes grâce à :

```php
require_once __DIR__ . '/../backend/vendor/autoload.php';
```

- Support PSR‑4
- Aucune inclusion manuelle de fichiers
- Modules injectables via DI

➡️ C’est la base d'une architecture moderne.

### ✔️ 2.2 Gestion des variables d’environnement (.env)

Selon l’environnement (dev, test, prod), `index.php` charge :

- `.env`
- `.env.test`
- `.env.azure`
- `.env.compose`

Cela permet :

- D'isoler les secrets
- D’adapter la configuration Docker/Azure
- De ne jamais exposer les mots de passe dans le code

➡️ Une pratique professionnelle très valorisée.

---

## 🔒 3. Initialisation de la sécurité

### ✔️ Forçage HTTPS (production)

Lorsque l’application tourne en production, `index.php` :

- Force la redirection vers HTTPS
- Ajoute l’en‑tête HSTS (HTTP Strict Transport Security)

Cela protège contre :

- Attaques man‑in‑the‑middle
- Downgrade HTTP→HTTPS
- Chargement mixte de ressources

➡️ Exactement ce que font les reverse‑proxies professionnels.

### ✔️ Gestion des erreurs (dev vs prod)

En développement :

- Affichage des erreurs PHP activé
- Logs complets

En production :

- Affichage désactivé
- Erreurs loguées
- Message générique côté client

➡️ Conforme aux bonnes pratiques OWASP.

---

## 🔌 4. Initialisation du conteneur d’injection de dépendances (DI)

Le fichier charge :

```php
require __DIR__ . '/../backend/config/container.php';
```

Ce conteneur conforme PSR‑11 gère :

- Services
- Repositories
- Contrôleurs
- Middlewares
- Drivers DB
- Logger

➡️ Cela permet une architecture entièrement modulaire, testable et découplée.

---

## 🛤️ 5. Système de routage

### ✔️ Chargement des routes API

Toutes les routes sont centralisées dans :

- `backend/api/routes.php`
- `backend/api/routes.auth.php`
- `backend/api/routes.menus.php`
- ...

Ces fichiers enregistrent :

- Handlers (contrôleurs)
- Middlewares
- Groupes de routes
- Routes paramétrées

➡️ L’organisation est claire et orientée domaine.

### ✔️ Routes de pages statiques (frontend)

Si la requête n’est pas une route API, `index.php` vérifie si une page HTML existe dans le frontend :

- `frontend/pages/home.html`
- `frontend/pages/login.html`
- etc.

Elles sont servies directement via PHP.

➡️ Cela permet une architecture Full‑Stack cohérente.

---

## 🔁 6. Cycle complet d’une requête

Voici le pipeline complet exécuté par `index.php` :

1. Charger l’environnement et les dépendances
2. Configurer la sécurité (HTTPS, HSTS)
3. Créer un objet Request
4. Instancier le Router et charger les routes
5. Trouver la route correspondante
6. Exécuter les middlewares associés
7. Appeler le contrôleur
8. Récupérer une instance Response
9. Envoyer le JSON/HTML au client

➡️ Ce cycle est équivalent à celui d’un framework moderne, mais 100% codé à la main.

---

## 🛡️ 7. Gestion globale des erreurs

Le fichier gère :

- ✔️ Exceptions techniques (backend) → converties en erreurs 500
- ✔️ Exceptions de routage :
	- 404 route non trouvée
	- 405 méthode non supportée
- ✔️ Exceptions d’authentification (gérées dans le router → 401 / 403)
- ✔️ Logging des erreurs critiques (tout est enregistré via le Logger PSR‑3)

➡️ Cette stratégie offre une vision claire de ce qui se passe en production.

---

## 🧪 8. Interopérabilité avec Docker et Azure

Le fichier `index.php` est compatible avec :

- Docker (en local et en CI)
- Azure App Service (prod)
- Azure CosmosDB (pour la partie MongoDB)
- Azure MySQL Flexible Server

Les `.env.azure*` permettent d’adapter automatiquement la configuration réseau, DNS et base de données selon l’environnement.

➡️ L’architecture backend peut fonctionner dans n’importe quel environnement cloud ou conteneurisé.

---

## 🧩 9. Pourquoi cette implémentation est professionnelle ?

🟩 **Respect du pattern Front Controller**  
Utilisé par Symfony, Laravel, Slim.

🟩 **Séparation claire frontend / backend**  
Le backend n’est jamais exposé.

🟩 **Sécurité intégrée (HTTPS + HSTS)**

🟩 **Environnement configurable et isolé**

🟩 **Routeur maison modulaire**

🟩 **Injection de dépendances PSR‑11**

🟩 **Gestion contrôlée des erreurs + logs**

🟩 **Approche maintenable et évolutive**

---

## 🏁 Conclusion

Le fichier `public/index.php` est bien plus qu’un simple point d’entrée.

Il constitue :

- 🧠 Le cerveau du backend
- 🔒 Le gardien de la sécurité
- 🚦 Le chef d’orchestre du cycle HTTP
- ⚙️ Le pivot entre routes, middlewares et contrôleurs
- 📦 Le glue code entre infrastructure, logique métier et frontend

Il démontre une maîtrise :

- Des architectures modernes
- Des patterns back-end
- Des standards PSR
- Des bonnes pratiques de sécurité
- Des principes de découplage et testabilité

Et s’intègre parfaitement dans une application PHP structurée, évolutive et professionnelle.