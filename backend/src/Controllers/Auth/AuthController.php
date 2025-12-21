<?php
namespace App\Controllers\Auth;

use App\Services\UserService;
use App\Services\AuthService;
use App\Services\MailerService;
use Monolog\Logger;
use App\Validators\UserValidator;

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
            $mainError = is_array($validation['errors']) && count($validation['errors']) > 0 ? reset($validation['errors']) : 'Des champs sont invalides.';
            return [
                'success' => false,
                'message' => 'Des champs sont invalides.',
                'mainError' => $mainError,
                'errors' => $validation['errors']
            ];
        }

        // 2. Hash du mot de passe
        $passwordHash = $this->authService->hashPassword($data['password']);
        $data['passwordHash'] = $passwordHash;
        unset($data['password']);

        // 3. Création de l'utilisateur en base
        try {
            $userId = $this->userService->createUser($data);
        } catch (\App\Exceptions\UserServiceException $e) {
            $this->logger->error('Échec création utilisateur', ['email' => $data['email'], 'code' => $e->getCode(), 'msg' => $e->getMessage()]);
            $errors = [];
            // Si c'est une collision d'email, on précise l'erreur sur le champ email
            if ($e->getCode() === \App\Exceptions\UserServiceException::EMAIL_EXISTS) {
                $errors['email'] = $e->getMessage();
            }
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $errors
            ];
        }

        // 4. Envoi de l'email de bienvenue
        $emailSent = $this->mailerService->sendWelcomeEmail($data['email'], $data['firstName']);
        if (!$emailSent) {
            $this->logger->error('Échec envoi email bienvenue', ['email' => $data['email']]);
            // On peut choisir de ne pas bloquer l'inscription, mais d'informer le client
            return [
                'success' => true,
                'userId' => $userId,
                'emailSent' => false,
                'message' => "Inscription réussie, mais l'email de bienvenue n'a pas pu être envoyé."
            ];
        }

        // 5. Gestion des erreurs et logs (déjà fait)
        // 6. Retourne la réponse (succès)
        return [
            'success' => true,
            'userId' => $userId,
            'emailSent' => true,
            'message' => 'Inscription réussie. Email de bienvenue envoyé.'
        ];
    }

    // D'autres méthodes (login, logout...) pourront être ajoutées ici
}
