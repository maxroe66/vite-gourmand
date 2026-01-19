<?php

namespace App\Middlewares;

use App\Core\Request;
use App\Exceptions\ForbiddenException;
use App\Exceptions\AuthException;

class RoleMiddleware
{
    /**
     * Vérifie si l'utilisateur a un des rôles requis.
     * @param Request $request
     * @param array $allowedRoles Liste des rôles autorisés (ex: ['ADMINISTRATEUR', 'EMPLOYE'])
     * @throws ForbiddenException Si le rôle n'est pas suffisant
     * @throws AuthException Si l'utilisateur n'est pas connecté
     */
    public function handle(Request $request, array $allowedRoles = []): void
    {
        // 1. L'utilisateur doit être authentifié (normalement géré par AuthMiddleware avant)
        $user = $request->getAttribute('user');
        
        if (!$user) {
            // Si AuthMiddleware n'a pas été appelé avant ou a échoué silencieusement (ce qui ne devrait pas arriver)
            throw AuthException::tokenMissing();
        }

        // 2. Si aucun rôle n'est spécifié, on laisse passer (juste besoin d'être auth)
        if (empty($allowedRoles)) {
            return;
        }

        // 3. Vérification du rôle
        // Le rôle est stocké dans le token JWT dans le champ 'role'
        if (!isset($user->role) || !in_array($user->role, $allowedRoles)) {
            throw new ForbiddenException("Accès refusé : vous n'avez pas les droits nécessaires pour effectuer cette action.");
        }
    }
}
