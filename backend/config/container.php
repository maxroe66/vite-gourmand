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
            $logFile = getenv('LOG_FILE');
            if ($logFile === false || trim($logFile) === '') {
                $logFile = '/tmp/app.log';
            }
            
            $logger = new Logger('ViteEtGourmand');
            $logger->pushHandler(new StreamHandler($logFile, Logger::DEBUG));
            return $logger;
        },

        // Les autres classes sont autowirées sans problème car elles dépendent
        // d'interfaces (LoggerInterface) ou d'autres classes (UserRepository, etc.).
    ]);

    return $containerBuilder->build();
};
