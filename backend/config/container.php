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

        App\Services\CsrfService::class => DI\autowire()
            ->constructorParameter('config', DI\get('config')),

        App\Middlewares\AuthMiddleware::class => DI\autowire()
            ->constructorParameter('config', DI\get('config')),

        App\Middlewares\CsrfMiddleware::class => DI\autowire(),

        App\Services\AuthService::class => DI\autowire()
            ->constructorParameter('config', DI\get('config')),

        App\Services\MailerService::class => DI\autowire()
            ->constructorParameter('config', DI\get('config')),

        App\Services\StorageService::class => DI\autowire()
            ->constructorParameter('config', DI\get('config')),

        // CorsMiddleware est instancié et exécuté globalement dans public/index.php
        App\Middlewares\CorsMiddleware::class => DI\autowire()
            ->constructorParameter('config', DI\get('config')),

        // SecurityHeadersMiddleware (CSP) — global dans public/index.php
        App\Middlewares\SecurityHeadersMiddleware::class => DI\autowire()
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
            $isCosmos = $mongoConfig['is_cosmos'] ?? false;
            $isVCore  = $mongoConfig['is_vcore'] ?? false;
            
            // Log de debug pour diagnostiquer les problèmes Azure
            $uriForLog = preg_replace('/\/\/([^:]+):([^@]+)@/', '//***:***@', $mongoConfig['uri']);
            error_log("[MongoDB Init] Tentative de connexion à: " . $uriForLog);
            error_log("[MongoDB Init] Base de données: " . $mongoConfig['database']);
            error_log("[MongoDB Init] Azure managed: " . ($isCosmos ? 'oui' : 'non') . ", vCore: " . ($isVCore ? 'oui' : 'non'));
            
            // Options URI (passées au driver libmongoc)
            $uriOptions = [];
            
            // Options driver PHP
            $driverOptions = [];
            
            if ($isVCore) {
                // DocumentDB vCore (mongocluster) : MongoDB standard avec TLS
                // mongodb+srv inclut déjà tls=true et authMechanism dans l'URI
                // On n'ajoute que les timeouts adaptés au cloud
                $uriOptions['serverSelectionTimeoutMS'] = 15000;
                $uriOptions['connectTimeoutMS'] = 10000;
                $uriOptions['socketTimeoutMS'] = 30000;
                // retryWrites=false est déjà dans l'URI DocumentDB vCore
                error_log("[MongoDB Init] Options DocumentDB vCore activées (timeouts cloud)");
            } elseif ($isCosmos) {
                // Cosmos DB Serverless RU (port 10255) : nécessite TLS + options spécifiques
                $uriOptions['serverSelectionTimeoutMS'] = 15000;
                $uriOptions['connectTimeoutMS'] = 10000;
                $uriOptions['socketTimeoutMS'] = 30000;
                $uriOptions['tls'] = true;
                $uriOptions['retryWrites'] = false;
                $uriOptions['retryReads'] = false;
                
                // Accepter le certificat Azure Cosmos DB
                $driverOptions = [
                    'allow_invalid_hostname' => true,
                    'context' => stream_context_create([
                        'ssl' => [
                            'allow_self_signed' => true,
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                        ],
                    ]),
                ];
                error_log("[MongoDB Init] Options Cosmos DB RU activées (TLS + hacks)");
            } else {
                // MongoDB standard (local / Docker)
                $uriOptions['serverSelectionTimeoutMS'] = 5000;
                $uriOptions['connectTimeoutMS'] = 5000;
                $uriOptions['socketTimeoutMS'] = 30000;
            }
            
            try {
                $client = new \MongoDB\Client($mongoConfig['uri'], $uriOptions, $driverOptions);
                
                // Test de connexion : ping (compatible Cosmos DB, contrairement à listDatabases)
                $client->selectDatabase($mongoConfig['database'])->command(['ping' => 1]);
                error_log("[MongoDB Init] Connexion réussie !");
                
                return $client;
            } catch (\Exception $e) {
                error_log("[MongoDB Init] ERREUR de connexion: " . $e->getMessage());
                error_log("[MongoDB Init] Type d'erreur: " . get_class($e));
                error_log("[MongoDB Init] URI (masquée): " . $uriForLog);
                // Retourner le client même en cas d'échec du ping
                // pour tenter les opérations individuelles
                return new \MongoDB\Client($mongoConfig['uri'], $uriOptions, $driverOptions);
            }
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
