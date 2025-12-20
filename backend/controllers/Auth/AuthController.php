<?php
namespace Backend\Controllers\Auth;

use Backend\Services\UserService;
use Backend\Services\AuthService;
use Backend\Services\MailerService;
use Monolog\Logger;
use Backend\Validators\UserValidator;
require_once __DIR__ . '/../../validators/UserValidator.php';

class AuthController
{
    private $userService;
    private $authService;
    private $mailerService;
    private $logger;

    public function __construct(
        UserService $userService,
        AuthService $authService,
        MailerService $mailerService,
        Logger $logger
    ) {
        $this->userService = $userService;
        $this->authService = $authService;
        $this->mailerService = $mailerService;
        $this->logger = $logger;
    }

    /**
     * Inscription d'un nouvel utilisateur
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        // 1. Validation des données
        $validation = UserValidator::validate($data);
        if (!$validation['isValid']) {
            $this->logger->warning('Échec validation inscription', $validation['errors']);
            return [
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validation['errors']
            ];
        }

        // 2. Hash du mot de passe
        $passwordHash = $this->authService->hashPassword($data['password']);
        $data['passwordHash'] = $passwordHash;
        unset($data['password']);

        // 3. Création de l'utilisateur en base
        $userId = $this->userService->createUser($data);
        if (!$userId) {
            $this->logger->error('Échec création utilisateur', ['email' => $data['email']]);
            return [
                'success' => false,
                'message' => "Erreur lors de la création de l'utilisateur."
            ];
        }

        // 4. Envoi de l'email de bienvenue (à compléter)
        // 5. Gestion des erreurs et logs (à compléter)
        // 6. Retourne la réponse (succès ou erreur)
        return [
            'success' => true,
            'userId' => $userId,
            'message' => 'Inscription réussie.'
        ];
    }

    // D'autres méthodes (login, logout...) pourront être ajoutées ici
}
