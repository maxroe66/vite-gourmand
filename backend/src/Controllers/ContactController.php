<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\ContactService;

/**
 * Contrôleur pour la gestion des messages de contact.
 * Accessible sans authentification (formulaire public).
 */
class ContactController
{
    private ContactService $contactService;

    public function __construct(ContactService $contactService)
    {
        $this->contactService = $contactService;
    }

    /**
     * Traite la soumission du formulaire de contact.
     *
     * @param Request $request Requête entrante (JSON body: titre, email, description)
     * @return Response
     */
    public function submit(Request $request): Response
    {
        $data = $request->getJsonBody();

        if (empty($data)) {
            return Response::json([
                'success' => false,
                'message' => 'Données invalides ou manquantes.'
            ], 400);
        }

        $result = $this->contactService->submitContact($data);

        if ($result['success']) {
            return Response::json($result, 201);
        }

        // Erreurs de validation → 422 Unprocessable Entity
        $statusCode = isset($result['errors']) ? 422 : 500;
        return Response::json($result, $statusCode);
    }
}
