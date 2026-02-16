<?php

namespace App\Middlewares;

use App\Core\Request;
use App\Exceptions\TooManyRequestsException;
use Psr\Log\LoggerInterface;

/**
 * Middleware de limitation de débit (rate limiting).
 *
 * Algorithme : fenêtre glissante basée sur fichiers (pas de dépendance Redis/Memcached).
 * Chaque clé (IP + route) est stockée dans un fichier contenant les timestamps
 * des requêtes dans la fenêtre courante.
 *
 * Paramètres passés via $args dans le routeur :
 *   - maxRequests (int)  : nombre maximal de requêtes autorisées dans la fenêtre
 *   - windowSeconds (int): durée de la fenêtre en secondes
 *   - prefix (string)    : préfixe pour isoler les compteurs par endpoint
 *
 * Exemple d'utilisation dans les routes :
 *   ->middleware(RateLimitMiddleware::class, ['maxRequests' => 5, 'windowSeconds' => 900, 'prefix' => 'login'])
 */
class RateLimitMiddleware
{
    private LoggerInterface $logger;
    private string $storageDir;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        // Stockage persistant dans backend/var/rate_limit/ (survit aux redémarrages)
        // Fallback sur /tmp si le dossier projet n'est pas accessible
        $projectDir = realpath(__DIR__ . '/../../var/rate_limit');
        if ($projectDir === false) {
            $candidate = __DIR__ . '/../../var/rate_limit';
            @mkdir($candidate, 0700, true);
            $projectDir = realpath($candidate);
        }
        $this->storageDir = $projectDir ?: sys_get_temp_dir() . '/vg_rate_limit';
    }

    /**
     * Point d'entrée du middleware.
     *
     * @param Request $request L'objet requête courant
     * @param array   $args    Paramètres : maxRequests, windowSeconds, prefix
     * @throws TooManyRequestsException si la limite est dépassée
     */
    public function handle(Request $request, array $args = []): void
    {
        // Désactiver le rate limiting en environnement de test
        $env = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: null;
        if ($env === 'test') {
            return;
        }

        $maxRequests   = (int)($args['maxRequests']   ?? 60);
        $windowSeconds = (int)($args['windowSeconds'] ?? 60);
        $prefix        = (string)($args['prefix']     ?? 'global');

        $result = $this->check($this->getClientIp(), $prefix, $maxRequests, $windowSeconds);

        if (!$result['allowed']) {
            $this->logger->warning('Rate limit dépassé', [
                'ip'       => $this->getClientIp(),
                'prefix'   => $prefix,
                'limit'    => $maxRequests,
                'window'   => $windowSeconds,
            ]);
            throw new TooManyRequestsException($result['retryAfter']);
        }
    }

    /**
     * Logique pure de rate limiting — testable sans effets de bord HTTP.
     *
     * @param string $identifier Identifiant du client (IP)
     * @param string $prefix     Préfixe pour isoler les compteurs
     * @param int    $maxRequests Nombre max de requêtes dans la fenêtre
     * @param int    $windowSeconds Durée de la fenêtre en secondes
     * @param float|null $now    Timestamp courant (injectable pour les tests)
     * @return array{allowed: bool, remaining: int, retryAfter: int}
     */
    public function check(
        string $identifier,
        string $prefix,
        int $maxRequests,
        int $windowSeconds,
        ?float $now = null
    ): array {
        $now = $now ?? microtime(true);
        $windowStart = $now - $windowSeconds;

        $file = $this->getFilePath($identifier, $prefix);
        $timestamps = $this->readTimestamps($file);

        // Purger les entrées hors fenêtre
        $timestamps = array_values(array_filter($timestamps, fn(float $ts) => $ts > $windowStart));

        if (count($timestamps) >= $maxRequests) {
            // Fenêtre pleine : calculer le temps avant la libération du plus ancien slot
            $oldestInWindow = min($timestamps);
            $retryAfter = max(1, (int)ceil(($oldestInWindow + $windowSeconds) - $now));

            return [
                'allowed'    => false,
                'remaining'  => 0,
                'retryAfter' => $retryAfter,
            ];
        }

        // Enregistrer la requête courante
        $timestamps[] = $now;
        $this->writeTimestamps($file, $timestamps);

        return [
            'allowed'    => true,
            'remaining'  => $maxRequests - count($timestamps),
            'retryAfter' => 0,
        ];
    }

    /**
     * Réinitialise le compteur d'un identifiant (utile pour les tests).
     */
    public function reset(string $identifier, string $prefix): void
    {
        $file = $this->getFilePath($identifier, $prefix);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    // ─── Méthodes privées ────────────────────────────────────────

    private function getClientIp(): string
    {
        // Priorité : X-Forwarded-For (derrière un proxy/LB), sinon REMOTE_ADDR
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Prendre la première IP (client original)
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function getFilePath(string $identifier, string $prefix): string
    {
        // Hash pour éviter les caractères spéciaux dans le nom de fichier  
        $hash = md5($prefix . ':' . $identifier);
        return $this->storageDir . '/' . $prefix . '_' . $hash . '.json';
    }

    /**
     * @return float[]
     */
    private function readTimestamps(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    /**
     * @param float[] $timestamps
     */
    private function writeTimestamps(string $file, array $timestamps): void
    {
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0700, true);
        }

        // Écriture atomique pour éviter les race conditions
        $tmp = $file . '.tmp.' . getmypid();
        file_put_contents($tmp, json_encode(array_values($timestamps)), LOCK_EX);
        rename($tmp, $file);
    }

    /**
     * Modifie le répertoire de stockage (pour les tests).
     */
    public function setStorageDir(string $dir): void
    {
        $this->storageDir = $dir;
    }
}
