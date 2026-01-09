<?php

namespace App\Controllers\Auth;

use App\Core\Request;
use App\Core\Response;
use App\Services\UserService;
use App\Services\AuthService;
use App\Services\MailerService;
use Psr\Log\LoggerInterface;
use App\Validators\UserValidator;
use App\Validators\LoginValidator;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\UserServiceException;

class AuthController
{
    private UserService $userService;
    private AuthService $authService;
    private MailerService $mailerService;
    private LoggerInterface $logger;
    private array $config;
    private UserValidator $userValidator;
    private LoginValidator $loginValidator;

    public function __construct(
        UserService $userService,
        AuthService $authService,
        MailerService $mailerService,
        LoggerInterface $logger,
        array $config,
        UserValidator $userValidator,
        LoginValidator $loginValidator
    ) {
        $this->userService = $userService;
        $this->authService = $authService;
        $this->mailerService = $mailerService;
        $this->logger = $logger;
        $this->config = $config;
        $this->userValidator = $userValidator;
        $this->loginValidator = $loginValidator;
    }

    /**
     * Inscription d'un nouvel utilisateur
     * @param Request|null $request Objet Request (null pour créer depuis globals)
     * @return array
     */
    public function register(?Request $request = null): array
    {
        // 0. Récupération et validation de l'input
        if ($request === null) {
            $request = Request::createFromGlobals();
        }
        
        $data = $request->getJsonBody();
        
        if (!$data) {
            return [
                'success' => false,
                'message' => 'Données invalides ou manquantes.'
            ];
        }

        // 1. Validation des données
        $validation = $this->userValidator->validate($data);
        if (!$validation['isValid']) {
            $this->logger->warning('Échec validation inscription', $validation['errors']);
            return (new Response())->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                                  ->setJsonContent([
                                      'success' => false,
                                      'message' => 'Des champs sont invalides.',
                                      'errors' => $validation['errors']
                                  ]);
        }

        // 2. Hash du mot de passe
        $passwordHash = $this->authService->hashPassword($data['password']);
        $data['passwordHash'] = $passwordHash;
        unset($data['password']);

        // 3. Création de l'utilisateur en base
        try {
            $userId = $this->userService->createUser($data);
        } catch (UserServiceException $e) {
            $this->logger->error('Échec création utilisateur', ['email' => $data['email'], 'code' => $e->getCode(), 'msg' => $e->getMessage()]);
            $errors = [];
            // Si c'est une collision d'email, on précise l'erreur sur le champ email
            if ($e->getCode() === UserServiceException::EMAIL_EXISTS) {
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
     * @param Request|null $request Objet Request (null pour créer depuis globals)
     * @return array
     */
    public function login(?Request $request = null): Response
    {
        // 1. Récupération et validation de l'input
        if ($request === null) {
            $request = Request::createFromGlobals();
        }
        
        $data = $request->getJsonBody();
        
        if (!$data) {
            return (new Response())->setStatusCode(Response::HTTP_BAD_REQUEST)
                                  ->setJsonContent(['success' => false, 'message' => 'Données invalides ou manquantes.']);
        }

        // 2. Validation des données avec LoginValidator
        $validation = $this->loginValidator->validate($data);
        if (!$validation['isValid']) {
            $this->logger->warning('Échec validation login', $validation['errors']);
            return (new Response())->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                                  ->setJsonContent([
                                      'success' => false,
                                      'message' => 'Des champs sont invalides.',
                                      'errors' => $validation['errors']
                                  ]);
        }

        // 3. Récupération de l'utilisateur par email
        try {
            $user = $this->userService->findByEmail($data['email']);
            
            // 4. Vérification du mot de passe avec protection contre les timing attacks
            if (!$user) {
                $dummyHash = '$2y$10$abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQR';
                try {
                    $this->authService->verifyPassword($data['password'], $dummyHash);
                } catch (InvalidCredentialsException $e) {
                    // Attendu
                }
                $this->logger->warning('Tentative de connexion avec email inexistant', ['email' => $data['email']]);
                
                return (new Response())->setStatusCode(Response::HTTP_UNAUTHORIZED)
                                      ->setJsonContent(['success' => false, 'message' => 'Email ou mot de passe incorrect.']);
            }
            
            // Vérification du mot de passe réel
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
            
            return (new Response())->setStatusCode(Response::HTTP_OK)
                                  ->setJsonContent([
                                      'success' => true,
                                      'userId' => $user['id'],
                                      'message' => 'Connexion réussie.'
                                  ]);

        } catch (InvalidCredentialsException $e) {
            $this->logger->warning('Échec de connexion: mot de passe incorrect', ['email' => $data['email']]);
            return (new Response())->setStatusCode(Response::HTTP_UNAUTHORIZED)
                                  ->setJsonContent(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la connexion', ['error' => $e->getMessage(), 'code' => $e->getCode()]);
            return (new Response())->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                                  ->setJsonContent(['success' => false, 'message' => 'Une erreur est survenue lors de la connexion.']);
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

        $this->logger->info('Utilisateur déconnecté avec succès');

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
