<?php

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Ce fichier retourne une FONCTION qui prend la config en paramètre.
// Cela supprime le besoin d'une variable globale.
return function (array $config): ContainerInterface {
    $containerBuilder = new ContainerBuilder();

    // Active l'autowiring (recommandé).
    // PHP-DI va automatiquement instancier les classes et leurs dépendances.
    $containerBuilder->useAutowiring(true);

    $containerBuilder->addDefinitions([
        // 1. On définit comment le conteneur peut accéder à la configuration.
        // N'importe quelle classe qui type-hint "array $config" dans son constructeur
        // ne le recevra PAS automatiquement. L'injection doit être explicite.
        'config' => $config,

        // 2. Définitions explicites pour les classes qui dépendent du tableau $config.
        // Sans cela, PHP-DI ne sait pas comment injecter un scalaire comme un tableau.
        App\Controllers\Auth\AuthController::class => DI\autowire()
            ->constructorParameter('config', DI\get('config')),

        App\Middlewares\AuthMiddleware::class => DI\autowire()
            ->constructorParameter('config', DI\get('config')),

        App\Services\AuthService::class => DI\autowire()
            ->constructorParameter('config', DI\get('config')),

        App\Services\MailerService::class => DI\autowire()
            ->constructorParameter('config', DI\get('config')),

        // CorsMiddleware est instancié et exécuté globalement dans public/index.php
        App\Middlewares\CorsMiddleware::class => DI\autowire()
            ->constructorParameter('config', DI\get('config')),

        // 3. Définition pour la connexion PDO (nécessite une configuration manuelle).
        PDO::class => function (ContainerInterface $c) {
            $dbConfig = $c->get('config')['db'];

            $pdoOptions = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ];

            if (isset($dbConfig['options']) && is_array($dbConfig['options'])) {
                $pdoOptions = $dbConfig['options'] + $pdoOptions;
            }

            return new \PDO(
                $dbConfig['dsn'],
                $dbConfig['user'],
                $dbConfig['pass'],
                $pdoOptions
            );
        },

        // 4. Définition pour le Logger (PSR-3).
        // On lie l'interface standard à notre implémentation Monolog.
        LoggerInterface::class => function (ContainerInterface $c) {
            $config = $c->get('config');
            $env = $config['env'] ?? 'development';

            $logFile = 'php://stderr'; // Toujours utiliser stderr en production/staging

            if ($env === 'development') {
                $logFileEnv = getenv('LOG_FILE');
                $logFileEnv = ($logFileEnv === false) ? '' : trim($logFileEnv);

                if ($logFileEnv === '') {
                    $logFile = __DIR__ . '/../logs/app.log';
                } else {
                    $logFile = $logFileEnv;
                }
            }

            $logger = new Logger('ViteEtGourmand');

            // Niveau de log selon l'environnement
            $logLevel = ($env === 'production') ? Logger::WARNING : Logger::DEBUG;

            $handler = new StreamHandler($logFile, $logLevel);

            // Format personnalisé pour meilleure lisibilité
            $handler->setFormatter(new \Monolog\Formatter\LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                "Y-m-d H:i:s",
                true,  // allowInlineLineBreaks
                true   // ignoreEmptyContextAndExtra
            ));

            $logger->pushHandler($handler);

            return $logger;
        },

        // 5. MongoDB Client (nécessite l'URI dans la config)
        \MongoDB\Client::class => function (ContainerInterface $c) {
            $mongoConfig = $c->get('config')['mongo'];
            // Si l'URI est vide (env de test sans mongo), on peut retourner null ou gérer l'erreur plus haut.
            // Ici, on retourne le client. Si la connexion échoue, la méthode syncMongoDB du service gère en try/catch.
            return new \MongoDB\Client($mongoConfig['uri']);
        },

        // 6. GoogleMapsService
        App\Services\GoogleMapsService::class => function (ContainerInterface $c) {
            $apiKey = $c->get('config')['google_maps']['api_key'] ?? '';
            return new App\Services\GoogleMapsService($apiKey);
        },

        // Injection explicite pour AvisService car il prend $config
        App\Services\AvisService::class => DI\autowire()
             ->constructorParameter('config', DI\get('config')),

        // Injection explicite pour CommandeService (signature modifiée)
        App\Services\CommandeService::class => DI\autowire()
            ->constructorParameter('mongoDbName', $config['mongo']['database'])
            ->constructorParameter('mongoDBClient', DI\get(\MongoDB\Client::class)),

        // Injection explicite pour StatsController pour éviter que le paramètre optionnel ne soit résolu à NULL
        App\Controllers\StatsController::class => DI\autowire()
             ->constructorParameter('mongoDbName', $config['mongo']['database'])
             ->constructorParameter('mongoDBClient', DI\get(\MongoDB\Client::class)),

        // Injection explicite pour CommandeController (MailerService, Logger, UserService)
        App\Controllers\CommandeController::class => DI\autowire()
            ->constructorParameter('mailerService', DI\get(App\Services\MailerService::class))
            ->constructorParameter('logger', DI\get(Psr\Log\LoggerInterface::class))
            ->constructorParameter('userService', DI\get(App\Services\UserService::class)),

        // Les autres classes comme `UserValidator` et `LoginValidator` sont maintenant
        // automatiquement instanciées et injectées grâce à l'autowiring car elles
        // n'ont pas de dépendances scalaires ou complexes dans leur constructeur.
    ]);

    return $containerBuilder->build();
};
