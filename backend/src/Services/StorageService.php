<?php

namespace App\Services;

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

class StorageService
{
    private $blobClient;
    private $containerName;
    private $useAzure = false;
    private $config;

    public function __construct(array $config = [])
    {
        // On récupère la config soit via injection (si fournie) soit via les variables d'environnement
        $this->config = $config;
        
        $connectionString = $_ENV['AZURE_STORAGE_CONNECTION_STRING'] ?? getenv('AZURE_STORAGE_CONNECTION_STRING');
        $this->containerName = $_ENV['AZURE_STORAGE_CONTAINER'] ?? getenv('AZURE_STORAGE_CONTAINER') ?? 'uploads';

        if ($connectionString) {
            // Nettoyage de la chaîne (espaces, guillemets, sauts de ligne importuns)
            $connectionString = trim($connectionString, " \t\n\r\0\x0B\"'");

            try {
                $this->blobClient = BlobRestProxy::createBlobService($connectionString);
                $this->useAzure = true;
            } catch (\Exception $e) {
                // Fallback to local if connection string is invalid
                error_log("Azure Storage Init Error: " . $e->getMessage());
            }
        }
    }

    public function upload(array $file, string $filename): string
    {
        if ($this->useAzure && $this->blobClient) {
            return $this->uploadToAzure($file, $filename);
        }

        return $this->uploadToLocal($file, $filename);
    }

    private function uploadToAzure(array $file, string $filename): string
    {
        $content = fopen($file['tmp_name'], 'r');
        
        try {
            // Créer le conteneur s'il n'existe pas (optionnel, peut être coûteux en perf, mieux vaut le faire une fois)
            // Pour l'instant, on suppose que le conteneur existe ou on gère l'erreur.
            
            $this->blobClient->createBlockBlob($this->containerName, $filename, $content);

            // Construction de l'URL publique
            // Format: https://<account_name>.blob.core.windows.net/<container_name>/<blob_name>
            
            $accountName = $this->getAccountName();
            if ($accountName) {
                return sprintf("https://%s.blob.core.windows.net/%s/%s", $accountName, $this->containerName, $filename);
            }
            
            // Si on ne trouve pas le nom de compte, on retourne null ou une erreur
            throw new \Exception("Impossible de déterminer l'URL du fichier Azure.");

        } catch (ServiceException $e) {
            error_log("Azure Upload Failed: " . $e->getMessage());
            
            $account = $this->getAccountName() ?? 'UNKNOWN';
            // Detailed contextual error
            throw new \Exception(sprintf(
                 "Azure Error [%s] Account:[%s] Container:[%s] Msg: %s",
                 $e->getCode(),
                 $account,
                 $this->containerName,
                 $e->getMessage()
            ));
        }
    }

    private function uploadToLocal(array $file, string $filename): string
    {
        $targetDir = __DIR__ . '/../../../public/assets/uploads/';
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetFile = $targetDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            chmod($targetFile, 0644);
            return '/assets/uploads/' . $filename;
        }

        throw new \Exception("Erreur lors de la sauvegarde locale du fichier");
    }

    private function getAccountName(): ?string
    {
        $connectionString = $_ENV['AZURE_STORAGE_CONNECTION_STRING'] ?? getenv('AZURE_STORAGE_CONNECTION_STRING');
        if (preg_match('/AccountName=([^;]+)/', $connectionString, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
