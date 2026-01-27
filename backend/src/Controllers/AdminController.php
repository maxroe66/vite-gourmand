<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\UserService;
use App\Services\MailerService;
use App\Services\AuthService;
use Psr\Log\LoggerInterface;
use App\Exceptions\UserServiceException;
use App\Validators\EmployeeValidator;

class AdminController
{
    private UserService $userService;
    private AuthService $authService;
    private MailerService $mailerService;
    private LoggerInterface $logger;
    private EmployeeValidator $employeeValidator;

    public function __construct(
        UserService $userService,
        AuthService $authService,
        MailerService $mailerService,
        LoggerInterface $logger,
        EmployeeValidator $employeeValidator
    ) {
        $this->userService = $userService;
        $this->authService = $authService;
        $this->mailerService = $mailerService;
        $this->logger = $logger;
        $this->employeeValidator = $employeeValidator;
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

        // Validation dédiée employé (email, mot de passe, prénom, nom)
        $validation = $this->employeeValidator->validate($data);
        if (!$validation['isValid']) {
            $this->logger->warning('Échec validation création employé', [
                'errors' => $validation['errors'],
                'email' => $data['email'] ?? null,
                'adminId' => $this->getAdminId($request)
            ]);
            return (new Response())->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->setJsonContent([
                    'success' => false,
                    'message' => 'Des champs sont invalides.',
                    'errors' => $validation['errors']
                ]);
        }

        // On définit le rôle et on prépare les data
        // On demande aussi prénom pour le mail, sinon par défaut "Employé"
        $userData = [
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'EMPLOYE',
            // Champs optionnels ou par défaut pour respecter le schéma DB
            'firstName' => $data['firstName'],
            'lastName' => $data['lastName'],
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'postalCode' => $data['postalCode'] ?? ''
        ];

        // Hash du mot de passe
        $userData['passwordHash'] = $this->authService->hashPassword($userData['password']);
        unset($userData['password']);
        
        try {
            $this->userService->createUser($userData);
            
            // Envoi email notification (SANS le mot de passe)
            $this->mailerService->sendEmployeeAccountCreated($userData['email'], $userData['firstName']);

            return (new Response())->setStatusCode(Response::HTTP_CREATED)
                ->setJsonContent(['success' => true, 'message' => 'Compte employé créé avec succès.']);
        } catch (UserServiceException $e) {
            $this->logger->warning('Conflit création employé', [
                'email' => $userData['email'] ?? null,
                'adminId' => $this->getAdminId($request),
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
            return (new Response())->setStatusCode(Response::HTTP_CONFLICT)
                ->setJsonContent(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur création employé', [
                'email' => $userData['email'] ?? null,
                'adminId' => $this->getAdminId($request),
                'error' => $e->getMessage()
            ]);
            return (new Response())->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setJsonContent(['success' => false, 'message' => 'Erreur serveur lors de la création.']);
        }
    }

    /**
     * Liste des employés
     * GET /api/admin/employees
     */
    public function getEmployees(Request $request): Response
    {
        $accessCheck = $this->checkAdminAccess($request);
        if ($accessCheck) return $accessCheck;

        try {
            $employees = $this->userService->getEmployees();
            return (new Response())->setJsonContent($employees);
        } catch (\Exception $e) {
            return (new Response())->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setJsonContent(['error' => 'Erreur lors de la récupération des employés.']);
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

    /**
     * Récupère l'identifiant de l'admin depuis le token décodé (attribut 'user').
     */
    private function getAdminId(Request $request): ?int
    {
        $user = $request->getAttribute('user');
        return isset($user->sub) ? (int) $user->sub : null;
    }
}
