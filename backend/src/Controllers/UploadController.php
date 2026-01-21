<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

class UploadController
{
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

        // Génération nom unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('menu_', true) . '.' . $extension;
        
        // Chemin cible (Relatif au fichier actuel)
        // S'adapte automatiquement à /var/www/html (Azure) ou /var/www/vite_gourmand (Local)
        $targetDir = __DIR__ . '/../../../public/assets/uploads/';
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $targetFile = $targetDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            // Reussite : on retourne l'URL relative
            // On s'assure que les permissions sont ok pour la lecture par Apache
            chmod($targetFile, 0644);
            
            return (new Response())
                ->setStatusCode(Response::HTTP_OK)
                ->setJsonContent([
                    'success' => true,
                    'url' => '/assets/uploads/' . $filename
                ]);
        } else {
             return (new Response())
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setJsonContent(['error' => 'Erreur lors de la sauvegarde du fichier']);
        }
    }
}
