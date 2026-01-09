<?php
// routes.test.php : routes uniquement pour l'environnement de test

use App\Core\Request;
use App\Core\Response;
use App\Repositories\UserRepository;
use App\Repositories\ResetTokenRepository;
use Psr\Container\ContainerInterface;

// Route pour récupérer le dernier token de reset pour un email
// ATTENTION: A n'activer qu'en environnement de test !
$router->get('/test/latest-reset-token', function (ContainerInterface $container, array $params, Request $request) {
    
    // Sécurité : vérifier qu'on est bien en environnement de test
    $config = $container->get('config');
    if (($config['env'] ?? 'production') !== 'test') {
        return (new Response())->setStatusCode(Response::HTTP_FORBIDDEN)
                              ->setJsonContent(['error' => 'Endpoint accessible en environnement de test uniquement.']);
    }

    $email = $request->getQueryParams()['email'] ?? null;
    if (!$email) {
        return (new Response())->setStatusCode(Response::HTTP_BAD_REQUEST)
                              ->setJsonContent(['error' => 'Paramètre email manquant.']);
    }

    $userRepository = $container->get(UserRepository::class);
    $user = $userRepository->findByEmail($email);

    if (!$user) {
        return (new Response())->setStatusCode(Response::HTTP_NOT_FOUND)
                              ->setJsonContent(['error' => 'Utilisateur non trouvé.']);
    }

    $resetTokenRepository = $container->get(ResetTokenRepository::class);
    $token = $resetTokenRepository->findLatestTokenForUser($user['id']);

    if (!$token) {
        return (new Response())->setStatusCode(Response::HTTP_NOT_FOUND)
                              ->setJsonContent(['error' => 'Aucun token de reset valide trouvé pour cet utilisateur.']);
    }

    return (new Response())->setJsonContent($token);
});
