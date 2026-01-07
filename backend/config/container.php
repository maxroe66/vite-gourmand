<?php

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

// Import des classes nécessaires
use App\Repositories\UserRepository;
use App\Services\UserService;
use App\Services\AuthService;
use App\Services\MailerService;
use App\Controllers\Auth\AuthController;
use App\Utils\MonologLogger;

$containerBuilder = new ContainerBuilder();

// Ajout des définitions de dépendances
$containerBuilder->addDefinitions([

    // 1. Définition pour la configuration globale
    'config' => function () {
        // La variable $config est injectée depuis index.php
        global $config;
        return $config;
    },

    // 2. Définition pour la connexion PDO
    PDO::class => function (ContainerInterface $c) {
        $config = $c->get('config')['db'];

        $pdoOptions = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ];

        if (isset($config['options']) && is_array($config['options'])) {
            $pdoOptions = $config['options'] + $pdoOptions;
        }

        return new \PDO(
            $config['dsn'],
            $config['user'],
            $config['pass'],
            $pdoOptions
        );
    },

    // 3. Définitions pour les Repositories (dépend de PDO)
    UserRepository::class => function (ContainerInterface $c) {
        return new UserRepository($c->get(PDO::class));
    },

    // 4. Définitions pour les Services
    UserService::class => function (ContainerInterface $c) {
        return new UserService($c->get(UserRepository::class));
    },
    AuthService::class => function (ContainerInterface $c) {
        return new AuthService($c->get('config'));
    },
    MailerService::class => function () {
        // MailerService n'a pas de dépendance dans son constructeur
        return new MailerService();
    },
    MonologLogger::class => function () {
        // Le logger est obtenu via une méthode statique
        return MonologLogger::getLogger();
    },

    // 5. Définition pour le Contrôleur (dépend des services)
    AuthController::class => function (ContainerInterface $c) {
        return new AuthController(
            $c->get(UserService::class),
            $c->get(AuthService::class),
            $c->get(MailerService::class),
            $c->get(MonologLogger::class), // Utilise la définition du logger
            $c->get('config')
        );
    },

]);

// Construire le conteneur
return $containerBuilder->build();
