<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\StorageService;
use Psr\Log\LoggerInterface;

class UploadController
{
    private $storageService;
    private LoggerInterface $logger;

    public function __construct(StorageService $storageService, LoggerInterface $logger)
    {
        $this->storageService = $storageService;
        $this->logger = $logger;
    }

    /**
     * Gère l'upload d'une image
     */
    public function uploadImage(Request $request): Response
    {
        $user = $request->getAttribute('user');

        if (!$user || !isset($user->role)) {
            return (new Response())
                ->setStatusCode(Response::HTTP_UNAUTHORIZED)
                ->setJsonContent(['error' => 'Non authentifié']);
        }

        $allowedRoles = ['ADMINISTRATEUR', 'EMPLOYE'];
        if (!in_array($user->role, $allowedRoles, true)) {
            return (new Response())
                ->setStatusCode(Response::HTTP_FORBIDDEN)
                ->setJsonContent(['error' => 'Accès interdit']);
        }

        // Vérification de la méthode
        if ($request->getMethod() !== 'POST') {
            return (new Response())
                ->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED)
                ->setJsonContent(['error' => 'Méthode non autorisée']);
        }

        $files = $request->getUploadedFiles();

        // Vérification de la présence du fichier
        if (!isset($files['image']) || $files['image']['error'] !== UPLOAD_ERR_OK) {
            return (new Response())
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJsonContent(['error' => 'Aucun fichier valide reçu']);
        }

        $file = $files['image'];
        
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

        // Génération nom unique avec extension contrôlée (mapping MIME -> extension)
        $extensionMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        $extension = $extensionMap[$mimeType] ?? 'bin';
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
             $this->logger->error('Upload failed', [
                 'error' => $e->getMessage(),
                 'mime' => $mimeType,
                 'filename' => $filename,
             ]);

             return (new Response())
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setJsonContent(['error' => 'Erreur upload: ' . $e->getMessage()]);
        }
    }
}
