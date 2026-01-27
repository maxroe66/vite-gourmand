
# 📘 Documentation Technique – Classe `Request`

Gestion centralisée et encapsulation des données HTTP

---

## 🎯 Objectif du document

Ce document présente au jury ECF la conception, la structure et le rôle de la classe `Request`, utilisée pour encapsuler entièrement les données HTTP dans le backend PHP développé sans framework.

**Il met en lumière :**

- Les principes de conception
- Les bonnes pratiques respectées
- Les parallèles avec les standards modernes (PSR‑7)
- La manière dont la `Request` interagit avec le `Router`, les `Middleware` et les `Controllers`
- Les avantages techniques pour la robustesse, la sécurité et la testabilité

---

## 🧭 1. Présentation générale

La classe `Request` représente l’objet central de toute requête HTTP dans l’application.

Son but est de remplacer l’utilisation directe de :

- `$_GET`
- `php://input`
- `$_POST`
- `$_FILES`

…et de fournir une API cohérente, typée, testable et structurée.

➡️ Cela permet à l’ensemble du backend d’être découplé de PHP natif et d’adopter une approche similaire aux frameworks modernes comme Laravel, Symfony, Slim.

---

## 🧱 2. Principes d’architecture

La classe `Request` respecte trois principes fondamentaux :

- 🟩 **Encapsulation**  
	Toutes les données HTTP sont centralisées dans un objet unique.
- 🟩 **Immutabilité fonctionnelle**  
	La `Request` n’est jamais modifiée directement par les controllers ; seules les méthodes prévues (ex : `setAttribute`) sont utilisées.
- 🟩 **Interopérabilité avec les middlewares**  
	Chaque middleware peut enrichir la requête avec des attributs sans la casser.

👉 C’est exactement le fonctionnement d’un `ServerRequestInterface` en PSR‑7.

---

## 🧬 3. Structure de la classe

La `Request` encapsule :

| Élément        | Rôle                                                        |
| -------------- | ----------------------------------------------------------- |
| `rawBody`      | Corps brut de la requête HTTP (JSON, XML, webhook…)         |
| `parsedBody`   | Corps parsé en JSON sous forme de tableau associatif        |
| `queryParams`  | Paramètres de la query string (`$_GET`)                     |
| `attributes`   | Données ajoutées par les middlewares (ex : utilisateur authentifié) |

Cette structuration permet une gestion propre, claire et performante du cycle HTTP.

---

## 🔍 4. Gestion du corps de la requête (JSON)

Le JSON est aujourd’hui le format dominant des API REST.

Ma classe `Request` implémente un parsing intelligent et robuste du body JSON :

- ✔️ **Lecture paresseuse (lazy loading)**  
	Le body n’est lu qu’une seule fois depuis :
	- `php://input`
	et ensuite mis en cache.
- ✔️ **Parsing automatique**  
	`$this->parsedBody = json_decode($rawBody, true);`
- ✔️ **Gestion des cas particuliers :**
	- body vide → `null`
	- JSON invalide → `null`
	- retour systématique en array et non en objet
	- méthode utilitaire `getJsonParam($key)`

➡️ Le comportement est identique à Slim, Laravel ou Symfony Request.

---

## 🧩 5. Attributs Request (middleware → controller)

Les middlewares ont souvent besoin d’attacher des données à la requête :

- utilisateur authentifié
- rôle de l’utilisateur
- données validées
- état de sécurité

Ma classe permet exactement cela :

- `setAttribute($key, $value)`
- `getAttribute($key)`

➡️ C’est le même mécanisme qu’en PSR‑7 (`withAttribute()`).
Cela permet de chaîner proprement les middlewares et controllers.

---

## 🌐 6. Gestion des Query Params

Les paramètres de type :

`/menus?page=2&limit=20`

sont récupérés via :

- `getQueryParams()`
- `getQueryParam($key)`

Sans toucher à `$_GET`, ce qui :

- renforce la propreté du code
- permet la testabilité
- évite les dépendances globales

---

## 🧪 7. Support complet pour les tests unitaires

La classe inclut deux méthodes exclusivement destinées aux tests :

- ✔️ `setRawBody($body)`
- ✔️ `setParsedBody($data)`

Très utile pour simuler :

- requêtes JSON
- erreurs de format
- middlewares modifiant le body

Aussi :

- ✔️ `createFromJson()`
	pour construire rapidement une `Request` factice dans PHPUnit.

➡️ La préparation testable de la `Request` est un énorme avantage pour un backend propre.

---

## 🔁 8. Création depuis le contexte global

Même si la classe encapsule tout, elle sait se construire toute seule :

```php
Request::createFromGlobals()
```

Ce qui la rend compatible avec le mode :

- prod
- dev
- tests
- mock HTTP

---

## 🛡️ 9. Avantages techniques et architecturaux

- 🟦 **Découplage total**  
	Aucune dépendance directe aux superglobales.  
	→ Architecture plus propre, modulaire et évolutive.
- 🟦 **Support middleware (PSR‑7 style)**  
	Les middlewares peuvent ajouter des informations dans la `Request`.  
	→ Architecture moderne, inspirée de Slim et Laravel.
- 🟦 **API cohérente**  
	Les controllers n'ont plus à se préoccuper de PHP natif.
- 🟦 **Testabilité maximale**  
	Utile pour les tests unitaires et d’intégration.
- 🟦 **Compatibilité avec les bonnes pratiques REST**  
	→ JSON natif  
	→ Query params abstraits  
	→ Attributs injectés

---

## 🏁 Conclusion

La classe `Request` que j’ai développée joue un rôle fondamental dans le backend.

Elle assure une gestion professionnelle et propre de la requête HTTP, tout en se calquant sur les standards modernes du développement back‑end :

- approche PSR‑7
- encapsulation
- immutabilité contrôlée
- testabilité
- modularité
- compatibilité middleware

Elle constitue, avec le `Router` et le système de middlewares, la base d’un véritable micro‑framework PHP conçu pour ce projet.