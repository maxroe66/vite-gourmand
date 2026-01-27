
# Documentation Technique – `container.php`
## Conteneur d’injection de dépendances (DI) avec PHP-DI + Monolog + PDO + MongoDB

Ce document explique en détail le fichier `backend/config/container.php`. Il décrit comment le conteneur **PSR-11** est construit, comment les services sont enregistrés et injectés (autowiring, définitions explicites), et comment la configuration applicative est propagée dans tout le backend.

---

## 🎯 Objectif
- Centraliser la création et la configuration de **toutes les dépendances** (services, contrôleurs, middlewares, clients DB, logger…).
- Éviter les **singletons globaux** et tout état partagé non maîtrisé.
- Rendre le code **testable**, **modulaire** et **évolutif** en s’appuyant sur **PHP-DI** (PSR‑11) et des interfaces PSR (PSR‑3 pour le logging).

---

## 🧱 Architecture générale
`container.php` **retourne une fonction** qui prend **`array $config`** en paramètre et renvoie une instance de **`Psr\Container\ContainerInterface`**. 

```php
return function (array $config): ContainerInterface { /* ... */ };
```

Ce choix évite l’usage de variables globales. Le fichier `public/index.php` charge d’abord `config.php` (qui retourne un tableau de configuration), puis **appelle** cette fonction pour construire le conteneur :

```php
$config = require __DIR__ . '/../backend/config/config.php';
$buildContainer = require __DIR__ . '/../backend/config/container.php';
$container = $buildContainer($config);
```

---

## ⚙️ Construction du conteneur
### 1) ContainerBuilder + Autowiring
```php
$containerBuilder = new ContainerBuilder();
$containerBuilder->useAutowiring(true);
```
**Autowiring activé** : PHP‑DI résout automatiquement les **classes** et leurs **dépendances objet** (services, repositories, contrôleurs), sans configuration manuelle, tant qu’il n’y a **pas de scalaires** (string, int, array…) non résolvables.

### 2) Enregistrement de la configuration
```php
'config' => $config,
```
La clé **`config`** devient une **entrée du conteneur**. Toute classe qui a besoin de configuration (par exemple clés API, DSN, variables JWT, CORS) peut y accéder via **injection explicite** (voir point suivant).

### 3) Définitions explicites pour paramètres scalaires
Certaines classes nécessitent des **scalaires** (ex. `array $config`, `string $mongoDbName`) non déductibles par l’autowiring. On utilise :

```php
DI::autowire()->constructorParameter('config', DI::get('config'))
```

Exemples dans le projet :
- `AuthController`, `AuthMiddleware`, `AuthService`, `MailerService`, `StorageService`, `AvisService` → injectent **`config`**
- `CommandeService`, `StatsController` → injectent **`mongoDbName`** (provenant de `config['mongo']['database']`) et **`MongoDB\Client`**
- `CommandeController` → injecte explicitement `MailerService`, `LoggerInterface`, `UserService`

### 4) Dépendances techniques définies dans le conteneur
#### a) **PDO (MySQL)**
```php
PDO::class => function (ContainerInterface $c) {
    $db = $c->get('config')['db'];
    // options + création PDO
}
```
- DSN/identifiants/options viennent de `config.php`
- Options **UTF‑8MB4**, **exceptions**, et **SSL** (Azure) si configurées

#### b) **Logger PSR‑3 (Monolog)**
```php
LoggerInterface::class => function (ContainerInterface $c) { /* Monolog */ }
```
- **Production** → sortie **stderr** (compatible **Docker** / **Azure**) 
- **Développement** → fichier `backend/logs/app.log` (surchargable via `LOG_FILE`)
- **Niveau de logs** : `DEBUG` en dev, `WARNING` en production
- **Format** lisible custom (date, canal, niveau, message)

#### c) **MongoDB\Client**
```php
MongoDB\Client::class => function (ContainerInterface $c) { /* ... */ }
```
- URI + base issus de `config['mongo']`
- Journalisation des tentatives de connexion (sans exposer les secrets)
- Test de connexion (`listDatabases()`) au démarrage → logs d’aide au diagnostic
- En cas d’exception, on retourne tout de même un client (les services géreront les erreurs)

#### d) **Services spécifiques**
Ex. `GoogleMapsService` reçoit sa **API key** depuis `config`

---

## 🧩 Pourquoi ce design ?
1. **Découplage fort** : aucune classe ne crée elle-même ses dépendances → inversion de contrôle (IoC)
2. **Testabilité** : on peut fournir des doublures (mocks/fakes) au conteneur pendant les tests
3. **Lisibilité** : toutes les règles d’instanciation sont centralisées
4. **Évolution** : remplacer Monolog, PDO ou Mongo par une autre implémentation ne touche pas le code métier
5. **Interopérabilité PSR** : Logger via **PSR‑3**, Container via **PSR‑11**

---

## 🔐 Gestion des secrets et environnements
- Le conteneur **ne lit jamais .env** directement : il reçoit déjà un **`$config` normalisé** (issu de `config.php` + Dotenv)
- Les secrets (JWT, mots de passe DB, clés API) ne sont **jamais** hardcodés ici
- Les comportements (niveau de logs, log file) dépendent de **`config['env']`**

---

## 🧪 Tests : bonnes pratiques
- **Override** facile : pendant les tests, on peut construire un conteneur avec un `$config` adapté et/ou surcharger des définitions :

```php
$builder = new ContainerBuilder();
$builder->useAutowiring(true);
$builder->addDefinitions([
    PDO::class => new FakePdo(),
    LoggerInterface::class => new NullLogger(),
]);
$container = $builder->build();
```

- **Injection ciblée** : pour un service qui consomme `PDO`, injecter **l’interface** (si vous l’avez) ou la classe concrète selon votre design.

---

## 🧠 Points d’attention & améliorations possibles
1. **Interfaces internes** : introduire des interfaces (ex. `DatabaseConnectionInterface`) et les lier à PDO/Mongo pour découpler encore plus les services métier.
2. **Découpage des définitions** : si le projet grossit, déplacer les définitions par domaines :
   - `config/di/http.php` (middlewares, controllers)
   - `config/di/infrastructure.php` (PDO, Mongo, Logger)
   - `config/di/services.php`
3. **Cache du conteneur** (prod) : activer la compilation PHP‑DI pour accélérer la résolution (si utile).
4. **Validation de config** : ajouter une passe de validation du tableau `$config` (ex. clés requises) avant la construction.
5. **Sécurité logs** : vérifier en continu que les logs n’exposent jamais d’**URI** avec identifiants. Ici un masquage est prévu (`***:***`).

---

## 🔗 Exemples d’usage
### Dans `public/index.php`
```php
$config = require __DIR__ . '/../backend/config/config.php';
$buildContainer = require __DIR__ . '/../backend/config/container.php';
$container = $buildContainer($config);

$router = new App\Core\Router();
// chargement des routes...
$response = $router->dispatch($method, $path, $container);
$response->send();
```

### Dans un contrôleur
```php
class CommandeController {
    public function __construct(
        private App\Services\MailerService $mailerService,
        private Psr\Log\LoggerInterface $logger,
        private App\Services\UserService $userService,
    ) {}
}
```
Ces dépendances sont **fournies automatiquement** par le conteneur selon les définitions.

---

## ✅ Résumé pour le jury
- Conteneur **PSR‑11** avec **autowiring** activé
- Définitions explicites pour **paramètres scalaires** (config, mongoDbName…)
- Enregistrements techniques : **PDO**, **MongoDB**, **Monolog**
- Sortie logs **stderr** en prod (bonne pratique Docker/Cloud)
- Masquage des secrets dans les logs
- Construction sans variable globale : `container.php` **retourne une fonction** qui reçoit la config
- Architecture **maintenable, testable, évolutive**

Ce conteneur est la **colonne vertébrale** de l’application : il orchestre la création de tous les objets et garantit un couplage faible entre la logique métier et l’infrastructure.
