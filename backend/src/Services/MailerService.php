<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailerService
{
    private LoggerInterface $logger;
    private array $config;

    public function __construct(LoggerInterface $logger, array $config)
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Envoie l'email de bienvenue
     * @param string $email
     * @param string $firstName
     * @return bool
     */
    public function sendWelcomeEmail(string $email, string $firstName): bool
    {
        try {
            // Vérifier si les credentials SMTP sont configurés
            if (empty($this->config['mail']['host']) || empty($this->config['mail']['user'])) {
                $this->logger->warning('Configuration SMTP manquante, email non envoyé', ['email' => $email]);
                return false;
            }

            $mail = new PHPMailer(true);

            // Configuration serveur SMTP
            $mail->isSMTP();
            $mail->Host = $this->config['mail']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['mail']['user'];
            $mail->Password = $this->config['mail']['pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            // Destinataires
            $mail->setFrom($this->config['mail']['from'], 'Vite & Gourmand');
            $mail->addAddress($email, $firstName);

            // Contenu
            $mail->isHTML(true);
            $mail->Subject = '🍽️ Bienvenue chez Vite & Gourmand !';
            
            // Charger le template HTML
            $templatePath = __DIR__ . '/../templates/emails/welcome.html';
            if (!file_exists($templatePath)) {
                $this->logger->error('Template email introuvable', ['path' => $templatePath]);
                return false;
            }

            $htmlBody = file_get_contents($templatePath);
            // Remplacer la variable {firstName}
            $htmlBody = str_replace('{firstName}', htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'), $htmlBody);
            $mail->Body = $htmlBody;

            // Version texte alternative
            $mail->AltBody = "Bienvenue {$firstName} !\n\n"
                . "Nous sommes ravis de vous accueillir parmi nos membres.\n\n"
                . "Votre inscription a été confirmée avec succès.\n\n"
                . "Bon appétit et à très bientôt,\n"
                . "L'équipe Vite & Gourmand";

            $mail->send();
            $this->logger->info('Email de bienvenue envoyé avec succès', ['email' => $email, 'firstName' => $firstName]);
            return true;

        } catch (Exception $e) {
            $this->logger->error('Échec envoi email de bienvenue', [
                'email' => $email,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }
}
