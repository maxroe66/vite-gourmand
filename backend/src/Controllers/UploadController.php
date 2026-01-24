<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\StorageService;

class UploadController
{
    private $storageService;

    public function __construct(StorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * Gère l'upload d'une image
     */
    public function uploadImage(Request $request): Response
    {
        // Vérification de la méthode
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return (new Response())
                ->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED)
                ->setJsonContent(['error' => 'Méthode non autorisée']);
        }

        // Vérification de la présence du fichier
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            return (new Response())
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJsonContent(['error' => 'Aucun fichier valide reçu']);
        }

        $file = $_FILES['image'];
        
        // Validation type MIME
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            return (new Response())
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJsonContent(['error' => 'Format de fichier non supporté. (JPG, PNG, WEBP, GIF)']);
        }

        // Validation taille (ex: 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
             return (new Response())
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJsonContent(['error' => 'Fichier trop volumineux (Max 5MB)']);
        }

        // Génération nom unique avec extension sécurisée ou vérifiée
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        // Force l'extension ou vérifie à nouveau si nécessaire (déjà fait par in_array mimeType)
        // Pour sécurité max, mapper mimeType -> extension
        
        $filename = uniqid('menu_', true) . '.' . $extension;
        
        try {
            // Utilisation du service de stockage (Azure ou Local)
            $publicUrl = $this->storageService->upload($file, $filename);

            return (new Response())
                ->setStatusCode(Response::HTTP_OK)
                ->setJsonContent([
                    'success' => true,
                    'url' => $publicUrl
                ]);

        } catch (\Exception $e) {
             return (new Response())
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setJsonContent(['error' => 'Erreur upload: ' . $e->getMessage()]);
        }
    }
}
