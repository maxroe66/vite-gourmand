
# 📘 Documentation Technique – Routeur Backend (`Router.php`)

---

## 🎯 Objectif du document

Ce document explique au jury ECF la conception, le fonctionnement et la logique interne du routeur développé from scratch (sans framework) dans le backend PHP.

**Il décrit :**

- Les concepts mis en place
- Les choix architecturaux
- Les bonnes pratiques respectées
- Les mécanismes avancés (middlewares, DI, exception handling, routing dynamique, etc.)

> Ce routeur constitue un élément central de l’application web.

---

## 🧭 1. Introduction générale

J’ai implémenté un routeur entièrement personnalisé, inspiré des principes utilisés dans des frameworks modernes (**Laravel, Slim, Symfony**), mais adapté à mon architecture.

**Il utilise :**

- Un front controller (`public/index.php`)
- Un moteur de routes flexible
- Une stack de middlewares comparable à Express.js / Laravel
- Une gestion des exceptions structurée
- Une injection de dépendances (DI) via PSR‑11
- Une abstraction Request/Response maison

**Objectif :** proposer une architecture professionnelle, testable, maintenable et claire.

---

## 🧱 2. Architecture du Router

### 📌 2.1 Déclaration des routes

Le routeur permet de définir des routes par méthode HTTP :

```php
$router->get('/menus', [...]);
$router->post('/auth/login', [...]);
$router->put('/commandes/{id}', [...]);
```

Chaque route contient :

- Un handler (contrôleur + méthode)
- Une liste de middlewares
- Un chemin paramétré, si nécessaire

👉 Cela correspond à la structure utilisée dans les micro‑frameworks modernes.

### 📌 2.2 Système de groupes de routes

Le routeur prend en charge les groupes de routes :

```php
$router->addGroup('/admin', function ($r) {
    $r->get('/stats', [...]);
});
```

Ce mécanisme permet :

- De grouper des routes par domaine (auth, admin, menus, commandes, etc.)
- D’appliquer un préfixe commun
- De clarifier l’architecture du projet

👉 Reproduit le comportement de Laravel (`Route::prefix()`) et Slim.

---

## 🛤️ 3. Routing dynamique (paramètres dans l'URL)

Les routes peuvent contenir des paramètres :

- `/menus/{id}`
- `/commandes/{id}`
- `/users/{user_id}`

Le routeur compile ce format en expression régulière :

`{ id }` → `(?P<id>[a-zA-Z0-9_]+)`

Ce mécanisme permet :

- D'extraire automatiquement les paramètres
- De les fournir au contrôleur sous forme d’array

👉 Fonction identique à FastRoute, Laravel et Symfony.

---

## 🧱 4. Système de Middlewares

Le routeur intègre une stack de middlewares exécutés avant le contrôleur, comme :

- `AuthMiddleware`
- `RoleMiddleware`
- `CorsMiddleware`

Grâce à la syntaxe :

```php
$router->get('/admin/dashboard', [...])
       ->middleware(AuthMiddleware::class)
       ->middleware(RoleMiddleware::class, ['admin']);
```

#### 🔧 Fonctionnement interne

Avant d’appeler le contrôleur, le routeur :

- Instancie chaque middleware via le container DI
- Exécute `handle($request, $args)`
- Interrompt la requête si un middleware lève une exception

👉 C'est le même principe que les middlewares Laravel/Slim.

---

## 🛡️ 5. Gestion des erreurs centralisée

Le router gère trois familles d’exceptions :

- ✔️ **AuthException** → `401 Unauthorized`  
  L'utilisateur n’est pas authentifié.
- ✔️ **ForbiddenException** → `403 Forbidden`  
  L'utilisateur est authentifié mais n’a pas le bon rôle.
- ✔️ **Exception générale** → `500 Internal Server Error`  
  Une erreur inattendue survient dans le contrôleur ou les middlewares.

Ces réponses sont renvoyées automatiquement sous forme JSON :

```json
{
  "success": false,
  "message": "Erreur..."
}
```

👉 Cette gestion d’erreurs centralisée repose sur le principe du Front Controller, appliqué par tous les frameworks modernes.

---

## 🔌 6. Injection de dépendances (DI) – PSR‑11

Mon routeur dépend d’un `ContainerInterface` PSR‑11 :

```php
public function dispatch(string $method, string $path, ContainerInterface $container)
```

Cela permet :

- D’obtenir les services via `$container->get()`
- D'injecter proprement les middlewares
- D’injecter loggers, services métier, repositories, etc.

👉 Le routeur est totalement découplé et testable.

---

## 📦 7. Exécution du contrôleur

Une fois :

- Les middlewares exécutés
- Les paramètres extraits
- La méthode trouvée

Le handler de la route est exécuté :

```php
$response = $route['handler']($container, $params, $request);
```

✔️ Le routeur impose que le contrôleur retourne un objet `Response`.

Cela garantit :

- Une sortie cohérente
- Des headers contrôlés
- Une structure maintenable

En cas de retour invalide → log critique + retour 500.

---

## 🧪 8. Testabilité

Grâce à :

- Objets `Request` & `Response`
- DI PSR‑11
- Aucune dépendance à des globals

➡️ Le routeur est totalement testable, ce qui est démontré dans mes tests PHPUnit.

---

## 🧬 9. Pourquoi ce routeur est professionnel ?

Voici les principes concrets qu’il respecte :

- 🟩 ✔️ **Front Controller Pattern**  
  Comme Symfony/Laravel.
- 🟩 ✔️ **Routes + Groups + Handlers**  
  Comme Slim ou FastRoute.
- 🟩 ✔️ **Middlewares chaînables**  
  Comme Express.js ou Laravel Middleware.
- 🟩 ✔️ **DI PSR‑11**  
  Mêmes standards que Symfony.
- 🟩 ✔️ **Exception Handling centralisé**  
  Bonnes pratiques d’architecture backend.
- 🟩 ✔️ **Typage strict, structure claire**  
  Code propre et maintenable.
- 🟩 ✔️ **Routing dynamique performant**  
  Patterns régex personnalisés.

---

## 🏁 Conclusion

Ce routeur constitue un mini‑framework PHP interne, développé entièrement from scratch.

Il met en œuvre :

- Des patterns d’architecture reconnus
- Une structure claire
- Une séparation des responsabilités (SRP)
- Un système de middlewares robuste
- Un routage dynamique flexible
- Une injection de dépendances conforme aux standards
- Une gestion d’erreurs centralisée et professionnelle

➡️ Le routeur est le cœur de mon backend.  
➡️ Il orchestre tout le cycle HTTP de manière propre et sécurisée.  
➡️ Il démontre une compréhension avancée des architectures backend modernes.