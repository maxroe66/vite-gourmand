<?php

namespace App\Controllers\Auth;

use App\Core\Request;
use App\Services\UserService;
use App\Services\AuthService;
use App\Services\MailerService;
use Psr\Log\LoggerInterface;
use App\Validators\UserValidator;
use App\Exceptions\InvalidCredentialsException;

class AuthController
{
    private UserService $userService;
    private AuthService $authService;
    private MailerService $mailerService;
    private LoggerInterface $logger;
    private array $config;

    public function __construct(
        UserService $userService,
        AuthService $authService,
        MailerService $mailerService,
        LoggerInterface $logger,
        array $config
    ) {
        $this->userService = $userService;
        $this->authService = $authService;
        $this->mailerService = $mailerService;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Inscription d'un nouvel utilisateur
     * @param array $data
     * @return array
     */
    public function register(): array
    {
        // 0. Récupération et validation de l'input
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            return [
                'success' => false,
                'message' => 'Données invalides ou manquantes.'
            ];
        }

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

        // 4. Génération du token JWT
        $role = $data['role'] ?? 'UTILISATEUR';
        $token = $this->authService->generateToken($userId, $role);

        // 5. Envoi du JWT dans un cookie httpOnly (sécurisé)
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $expire = time() + ($this->config['jwt']['expire'] ?? 3600);

        setcookie('authToken', $token, [
            'expires' => $expire,
            'path' => '/',
            'secure' => $isSecure,      // HTTPS uniquement en production
            'httponly' => true,         // Inaccessible en JavaScript
            'samesite' => 'Lax'        // Protection CSRF
        ]);

        // 6. Envoi de l'email de bienvenue
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

        // 7. Gestion des erreurs et logs (déjà fait)
        // 8. Retourne la réponse (succès) - le token est dans le cookie
        return [
            'success' => true,
            'userId' => $userId,
            'emailSent' => true,
            'message' => 'Inscription réussie et email de bienvenue envoyé.'
        ];
    }

    /**
     * Connexion d'un utilisateur existant
     * @return array
     */
    public function login(): array
    {
        // 1. Récupération et validation de l'input
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            return [
                'success' => false,
                'message' => 'Données invalides ou manquantes.'
            ];
        }

        // 2. Validation des champs requis (email et password)
        $errors = [];
        
        if (empty($data['email'])) {
            $errors['email'] = 'L\'email est requis.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'email n\'est pas valide.';
        }
        
        if (empty($data['password'])) {
            $errors['password'] = 'Le mot de passe est requis.';
        }
        
        if (!empty($errors)) {
            $this->logger->warning('Échec validation login', $errors);
            return [
                'success' => false,
                'message' => 'Des champs sont invalides.',
                'errors' => $errors
            ];
        }

        // 3. Récupération de l'utilisateur par email
        try {
            $user = $this->userService->findByEmail($data['email']);
            
            if (!$user) {
                // L'email n'existe pas en base
                $this->logger->warning('Tentative de connexion avec email inexistant', ['email' => $data['email']]);
                throw InvalidCredentialsException::invalidCredentials();
            }

            // 4. Vérification du mot de passe
            $this->authService->verifyPassword($data['password'], $user['passwordHash']);

            // 5. Génération du token JWT
            $token = $this->authService->generateToken((int)$user['id'], $user['role']);

            // 6. Envoi du JWT dans un cookie httpOnly (sécurisé)
            $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            $expire = time() + ($this->config['jwt']['expire'] ?? 3600);

            setcookie('authToken', $token, [
                'expires' => $expire,
                'path' => '/',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            // 7. Retourne la réponse de succès
            $this->logger->info('Connexion réussie', ['userId' => $user['id'], 'email' => $data['email']]);
            
            return [
                'success' => true,
                'userId' => $user['id'],
                'message' => 'Connexion réussie.'
            ];

        } catch (InvalidCredentialsException $e) {
            // Credentials invalides (email inexistant ou mot de passe incorrect)
            $this->logger->warning('Échec de connexion: credentials invalides');
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            // Erreur imprévue
            $this->logger->error('Erreur lors de la connexion', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors de la connexion.'
            ];
        }
    }

    public function logout(): array
    {
        // 1. Invalider le cookie en le supprimant
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

        setcookie('authToken', '', [
            'expires' => time() - 3600, // Expiré dans le passé
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        // 2. Répondre avec succès
        return [
            'success' => true,
            'message' => 'Déconnexion réussie.'
        ];
    }

    public function checkAuth(Request $request): array
    {
        // Le middleware a déjà fait la vérification et a enrichi l'objet Request.
        // On récupère les données du token décodé depuis l'attribut 'user'.
        $decodedToken = $request->getAttribute('user');

        if ($decodedToken) {
            return [
                'isAuthenticated' => true,
                'user' => [
                    'id' => $decodedToken->sub, // 'sub' est le standard pour l'ID utilisateur
                    'role' => $decodedToken->role
                ]
            ];
        }

        // Ce cas ne devrait pas arriver si le middleware est bien configuré sur la route
        $this->logger->error("checkAuth atteint sans attribut 'user' dans la requête. Le middleware a-t-il échoué ?");
        return ['isAuthenticated' => false];
    }
}
