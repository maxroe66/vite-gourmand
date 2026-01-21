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
    try {
        // Sécurité : vérifier qu'on est bien en environnement de test ou de développement
        $config = $container->get('config');
        if (!in_array(($config['env'] ?? 'production'), ['test', 'development'])) {
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
    } catch (\Throwable $e) {
        // Log l'erreur pour le débogage
        error_log('Error in /test/latest-reset-token: ' . $e->getMessage());
        // Retourne une réponse JSON générique en cas d'erreur inattendue
        return (new Response())->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                              ->setJsonContent(['error' => 'Une erreur interne est survenue.', 'details' => $e->getMessage()]);
    }
});
