<?php

require __DIR__ . '/routes.auth.php';
require __DIR__ . '/routes.menus.php';
require __DIR__ . '/routes.commandes.php';
require __DIR__ . '/routes.avis.php';
require __DIR__ . '/routes.admin.php';
require __DIR__ . '/routes.materiel.php'; // Routes matériel
require __DIR__ . '/routes.diagnostic.php'; // Route de diagnostic MongoDB

use App\Controllers\UploadController;
use Psr\Container\ContainerInterface;
use App\Core\Request;

// Route upload
$router->post('/upload', function (ContainerInterface $container, array $params, Request $request) {
    // TODO: Ajouter middleware Auth + Role Admin/Employe
    // On utilise le conteneur pour résoudre les dépendances (StorageService)
    $controller = $container->get(UploadController::class);
    return $controller->uploadImage($request);
});

// routes.php : point d'entrée pour toutes les routes de l'API
