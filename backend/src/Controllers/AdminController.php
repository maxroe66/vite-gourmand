<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\UserService;
use App\Services\MailerService;
use App\Services\AuthService;
use Psr\Log\LoggerInterface;
use App\Exceptions\UserServiceException;

class AdminController
{
    private UserService $userService;
    private AuthService $authService;
    private MailerService $mailerService;
    private LoggerInterface $logger;

    public function __construct(
        UserService $userService,
        AuthService $authService,
        MailerService $mailerService,
        LoggerInterface $logger
    ) {
        $this->userService = $userService;
        $this->authService = $authService;
        $this->mailerService = $mailerService;
        $this->logger = $logger;
    }

    /**
     * Middleware de vérification Admin
     */
    private function checkAdminAccess(Request $request): ?Response
    {
        $user = $request->getAttribute('user');
        if (!$user || $user->role !== 'ADMINISTRATEUR') {
            return (new Response())->setStatusCode(Response::HTTP_FORBIDDEN)
                ->setJsonContent(['success' => false, 'message' => 'Accès refusé. Privilèges administrateur requis.']);
        }
        return null;
    }

    /**
     * Création d'un compte employé
     * POST /api/admin/employees
     */
    public function createEmployee(Request $request): Response
    {
        $accessCheck = $this->checkAdminAccess($request);
        if ($accessCheck) return $accessCheck;

        $data = $request->getJsonBody();

        if (empty($data['email']) || empty($data['password'])) {
            return (new Response())->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJsonContent(['success' => false, 'message' => 'Email et mot de passe requis.']);
        }

        // On définit le rôle et on prépare les data
        // On demande aussi prénom pour le mail, sinon par défaut "Employé"
        $userData = [
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'EMPLOYE',
            // Champs optionnels ou par défaut pour respecter le schéma DB
            'firstName' => $data['firstName'] ?? 'Employé',
            'lastName' => $data['lastName'] ?? 'V&G',
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'postalCode' => $data['postalCode'] ?? ''
        ];

        // Hash du mot de passe
        $userData['passwordHash'] = $this->authService->hashPassword($userData['password']);
        
        try {
            $this->userService->createUser($userData);
            
            // Envoi email notification (SANS le mot de passe)
            $this->mailerService->sendEmployeeAccountCreated($userData['email'], $userData['firstName']);

            return (new Response())->setStatusCode(Response::HTTP_CREATED)
                ->setJsonContent(['success' => true, 'message' => 'Compte employé créé avec succès.']);
        } catch (UserServiceException $e) {
            return (new Response())->setStatusCode(Response::HTTP_CONFLICT)
                ->setJsonContent(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->logger->error("Erreur création employé: " . $e->getMessage());
            return (new Response())->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setJsonContent(['success' => false, 'message' => 'Erreur serveur lors de la création.']);
        }
    }

    /**
     * Désactiver un compte utilisateur
     * PATCH /api/admin/users/{id}/disable
     */
    public function disableUser(array $params, Request $request): Response
    {
        $accessCheck = $this->checkAdminAccess($request);
        if ($accessCheck) return $accessCheck;

        $userId = (int)$params['id'];

        try {
            $this->userService->disableUser($userId);
            return (new Response())->setStatusCode(Response::HTTP_OK)
                ->setJsonContent(['success' => true, 'message' => 'Utilisateur désactivé avec succès.']);
        } catch (\Exception $e) {
            return (new Response())->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setJsonContent(['success' => false, 'message' => 'Erreur lors de la désactivation.']);
        }
    }
}
