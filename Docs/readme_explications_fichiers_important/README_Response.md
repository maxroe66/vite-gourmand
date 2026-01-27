
# 📘 Documentation Technique – Classe `Response`

---

## 🎯 Objectif du document

Ce document explique au jury ECF la conception et le fonctionnement de la classe `Response`, utilisée dans le backend PHP pour construire et envoyer les réponses HTTP.

**Il décrit :**

- La structure interne de la classe
- La gestion des codes de statut et des en‑têtes
- Le support JSON
- La compatibilité avec une architecture “framework‑like”
- Les bonnes pratiques suivies (pattern Response Object, séparation contrôleur/transport, clean architecture)



---

## 🧭 1. Rôle principal de la classe `Response`

La classe `Response` encapsule entièrement la réponse HTTP envoyée au client.

Elle permet de construire une réponse :

- typée
- claire
- contrôlée
- sans utilisation directe de `echo`, `header()` ou `http_response_code()` dans les contrôleurs

Cela permet :

- ✔️ une meilleure testabilité
- ✔️ un code propre et cohérent
- ✔️ une architecture moderne (approche utilisée par Laravel, Symfony, Slim)
- ✔️ une séparation stricte entre logique métier et transport HTTP

> Le contrôleur retourne un objet `Response`, et seul l’index.php l’envoie réellement via `$response->send()`.


---

## 🧱 2. Structure globale de la classe

La classe encapsule trois éléments essentiels :

| Élément        | Rôle                                                        |
| -------------- | ----------------------------------------------------------- |
| `statusCode`   | Code de statut HTTP (200, 404, 500, etc.)                  |
| `headers`      | Tableau associatif des en‑têtes HTTP                       |
| `body`         | Corps de la réponse (string, JSON, etc.)                   |

Cette encapsulation est le cœur du pattern Response Object utilisé dans les micro‑frameworks.


---

## 🟦 3. Codes HTTP (constants)

La classe définit des constantes :

```php
HTTP_OK = 200
HTTP_BAD_REQUEST = 400
HTTP_UNAUTHORIZED = 401
HTTP_FORBIDDEN = 403
HTTP_NOT_FOUND = 404
HTTP_INTERNAL_SERVER_ERROR = 500
```

**Avantages :**

- ✔️ Code lisible
- ✔️ Pas de “magical numbers”
- ✔️ Conformité aux standards HTTP
- ✔️ Facilite les retours JSON cohérents dans les contrôleurs


---

## 🧬 4. Construction d’une réponse

La classe peut être instanciée ainsi :

```php
$response = new Response("Hello", 200, ["Content-Type" => "text/plain"]);
```

Elle supporte le pattern fluent :

```php
$response
    ->setStatusCode(201)
    ->setHeader("X-API-Version", "1.0")
    ->setContent("Created");
```

➡️ Ce style fluide améliore la lisibilité et rappelle les frameworks modernes.


---

## 🔍 5. Support JSON natif

La méthode :

```php
setJsonContent($data)
```

permet de :

- définir automatiquement le `Content-Type: application/json`
- convertir la donnée en JSON via `json_encode`
- produire une réponse UTF‑8 propre, sans slashs échappés

**Exemple :**

```php
return (new Response())
    ->setStatusCode(Response::HTTP_CREATED)
    ->setJsonContent([
        "success" => true,
        "userId" => 42
    ]);
```

**Pourquoi c’est important ?**

- toutes les API REST modernes utilisent JSON
- cela évite les erreurs de header envoyés trop tôt
- cela centralise l’encodage JSON (bonnes pratiques)
- cela permet des tests unitaires simples



---

## 🛑 6. Méthode JSON statique (dépréciée)

```php
public static function json($data, int $status = 200)
```

Cette méthode est marquée comme `@deprecated`, car elle contient un `exit`, ce qui :

- rend la testabilité difficile
- rompt la chaîne d’exécution
- force un style de code impératif

Elle est conservée uniquement pour rétrocompatibilité.

➡️ La nouvelle approche (orientée objet) consiste à retourner un `Response` et à laisser `index.php` envoyer la réponse.


---

## 📨 7. Méthode `send()`

C’est la méthode finale appelée par `index.php`, responsable de :

1. Envoyer le code HTTP
    ```php
    http_response_code($this->statusCode);
    ```
2. Envoyer tous les en‑têtes
    ```php
    foreach ($this->headers as $name => $value) {
         header("$name: $value");
    }
    ```
3. Envoyer le contenu
    ```php
    echo $this->content;
    ```

**Pourquoi l’envoi est centralisé ?**

- ✔️ Évite les `echo` dispersés partout
- ✔️ Permet le buffering, le logging, les hooks
- ✔️ Rapprochement des pratiques Symfony/Laravel
- ✔️ `index.php` contrôle le cycle HTTP entièrement


---

## 🧪 8. Testabilité

Le fait que la réponse soit un objet rend :

- les tests PHPUnit simples
- l’assertion du contenu facile
- l’inspection des headers possible
- la simulation de réponses sans les envoyer réalisable

**Exemple test :**

```php
$response = (new Response())->setJsonContent(['ok' => true]);
$this->assertEquals('{"ok":true}', $response->getContent());
$this->assertEquals('application/json; charset=utf-8', $response->getHeaders()['Content-Type']);
```

➡️ Ceci est impossible avec des `echo` et `header()` directs.


---

## 🛡️ 9. Sécurité et cohérence

La classe impose :

- un type clair pour la réponse
- une séparation propre contenu / en‑têtes / status
- un point d’envoi unique
- la certitude que les headers ne seront pas envoyés trop tôt

C’est un élément essentiel pour :

- éviter les leaks de headers
- gérer le CORS proprement
- gérer les cookies si nécessaire
- gérer les erreurs JSON globales



---

## 🏁 Conclusion

La classe `Response` constitue un pilier essentiel du backend.

Elle apporte une architecture professionnelle, basée sur :

- le pattern Response Object
- une séparation propre des responsabilités
- la centralisation du cycle HTTP
- la testabilité
- la compatibilité avec le router et l’index
- une gestion haut‑niveau des codes HTTP et JSON

Elle s’intègre parfaitement dans la logique globale du projet, où j’ai recréé :

- ✔️ un front controller
- ✔️ un routeur avancé
- ✔️ une Request et Response orientées objet
- ✔️ un système de middlewares
- ✔️ un container DI
- ✔️ des services, repositories, contrôleurs structurés

Ensemble, ces éléments forment un mini framework PHP professionnel, développé entièrement from scratch.