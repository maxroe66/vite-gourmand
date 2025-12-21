<?php
namespace Backend\Services;

class MailerService
{
    /**
     * Envoie l'email de bienvenue
     * @param string $email
     * @param string $firstName
     * @return bool
     */
    public function sendWelcomeEmail(string $email, string $firstName): bool
    {
        // Envoi de l'email via PHPMailer ou autre
        return false;
    }
}
