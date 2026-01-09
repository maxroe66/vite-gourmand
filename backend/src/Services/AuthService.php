<?php

namespace App\Services;

use App\Exceptions\InvalidCredentialsException;
use Psr\Log\LoggerInterface;

use App\Repositories\UserRepository;
use App\Repositories\ResetTokenRepository;
use Exception;

class AuthService
{
    private array $config;
    private LoggerInterface $logger;
    private UserRepository $userRepository;
    private ResetTokenRepository $resetTokenRepository;
    private MailerService $mailerService;

    public function __construct(
        array $config, 
        LoggerInterface $logger,
        UserRepository $userRepository,
        ResetTokenRepository $resetTokenRepository,
        MailerService $mailerService
    )
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->userRepository = $userRepository;
        $this->resetTokenRepository = $resetTokenRepository;
        $this->mailerService = $mailerService;
    }

    /**
     * Initie la procédure de mot de passe oublié
     * @param string $email
     * @return bool
     */
    public function requestPasswordReset(string $email): bool
    {
        $user = $this->userRepository->findByEmail($email);
        
        // Pour sécurité, on ne dit pas si l'email existe ou non, donc on retourne true
        // (technique "Generic Response")
        if (!$user) {
            $this->logger->info('Demande reset password pour email inconnu', ['email' => $email]);
            return true; 
        }

        // Générer un token aléatoire sécurisé
        $token = bin2hex(random_bytes(32)); // 64 chars
        
        // Expiration 1 heure
        $expiration = date('Y-m-d H:i:s', time() + 3600);
        
        // Sauvegarder en base
        $this->resetTokenRepository->create($user['id'], $token, $expiration);
        
        // Envoyer email
        return $this->mailerService->sendPasswordResetEmail($user['email'], $token, $user['prenom']);
    }

    /**
     * Réinitialise le mot de passe avec un token
     * @param string $token
     * @param string $newPassword
     * @return bool
     * @throws Exception Si token invalide (pour message erreur précis au controller si besoin)
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        // 1. Trouver le token valide
        $resetToken = $this->resetTokenRepository->findByToken($token);
        
        if (!$resetToken) {
            $this->logger->warning('Tentative reset password avec token invalide/expiré', ['token' => $token]);
            throw new Exception('Token invalide ou expiré.');
        }

        // 2. Hasher le nouveau mot de passe
        $hash = $this->hashPassword($newPassword);
        
        // 3. Mettre à jour l'utilisateur
        $this->userRepository->updatePassword($resetToken['id_utilisateur'], $hash);
        
        // 4. Marquer le token comme utilisé
        $this->resetTokenRepository->markAsUsed($resetToken['id_token']);
        
        $this->logger->info('Mot de passe réinitialisé avec succès', ['userId' => $resetToken['id_utilisateur']]);
        
        return true;
    }

    /**
     * Hash le mot de passe
     * @param string $password
     * @return string
     */
    public function hashPassword(string $password): string
    {
        // Utilise password_hash avec l'algorithme par défaut (bcrypt ou Argon2)
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Vérifie le mot de passe et lève une exception si invalide.
     * 
     * @param string $password Le mot de passe en clair fourni par l'utilisateur
     * @param string $hash Le hash stocké en base de données
     * @return void
     * @throws InvalidCredentialsException Si le mot de passe ne correspond pas au hash
     */
    public function verifyPassword(string $password, string $hash): void
    {
        if (!password_verify($password, $hash)) {
            $this->logger->warning('Tentative de connexion avec mot de passe invalide');
            throw InvalidCredentialsException::invalidCredentials();
        }
        
        $this->logger->info('Mot de passe vérifié avec succès');
    }

    /**
     * Génère un JWT
     * @param int $userId
     * @param string $role
     * @return string
     */
    public function generateToken(int $userId, string $role): string
    {
        $secret = $this->config['jwt']['secret'];
        $expire = $this->config['jwt']['expire'];

        $payload = [
            'iss' => 'vite-gourmand',  // émetteur
            'sub' => $userId,           // sujet (user ID)
            'role' => $role,
            'iat' => time(),            // émis à
            'exp' => time() + $expire   // expire à
        ];

        $token = \Firebase\JWT\JWT::encode($payload, $secret, 'HS256');
        $this->logger->info('Token JWT généré', ['userId' => $userId, 'role' => $role]);

        return $token;
    }
}
