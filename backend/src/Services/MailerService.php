<?php

namespace App\Services;

use Psr\Log\LoggerInterface;

class MailerService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Envoie l'email de bienvenue
     * @param string $email
     * @param string $firstName
     * @return bool
     */
    public function sendWelcomeEmail(string $email, string $firstName): bool
    {
        // Envoi de l'email via PHPMailer ou autre
        // Pour l'instant, retourne false (non implémenté)
        $this->logger->warning('Envoi email de bienvenue non implémenté', ['email' => $email, 'firstName' => $firstName]);
        return false;
    }
}
