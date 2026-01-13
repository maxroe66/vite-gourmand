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
     * Factory method pour créer une instance PHPMailer
     * Permet l'injection de mock dans les tests
     * @return PHPMailer
     */
    protected function createMailer(): PHPMailer
    {
        return new PHPMailer(true);
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

            $mail = $this->createMailer();

            // Configuration serveur SMTP
            $mail->isSMTP();
            $mail->Host = $this->config['mail']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['mail']['user'];
            $mail->Password = $this->config['mail']['pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            // WORKAROUND: Avast Antivirus intercepte le SSL en local, ce qui cause une erreur car il remplace le certificat par le sien qui n'est pas valide.
            // On désactive la vérification uniquement pour Mailtrap en dev.
            if ($this->config['mail']['host'] === 'sandbox.smtp.mailtrap.io') {
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
            }

            // Destinataires
            $mail->setFrom($this->config['mail']['from'], 'Vite & Gourmand');
            $mail->addAddress($email, $firstName);

            // Contenu
            $mail->isHTML(true);
            $mail->Subject = '🍽️ Bienvenue chez Vite & Gourmand !';
            
            // Charger le template HTML
            $templatePath = __DIR__ . '/../../templates/emails/welcome.html';
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

    /**
     * Envoie l'email de réinitialisation de mot de passe
     * @param string $email
     * @param string $token
     * @param string $firstName
     * @return bool
     */
    public function sendPasswordResetEmail(string $email, string $token, string $firstName): bool
    {
        // Log explicite pour debug
        $this->logger->info('Tentative envoi email reset', [
            'email' => $email,
            'host' => $this->config['mail']['host'] ?? null,
            'user' => $this->config['mail']['user'] ?? null,
            'from' => $this->config['mail']['from'] ?? null,
            'env' => $this->config['env'] ?? null,
        ]);
        try {
            if (empty($this->config['mail']['host'])) {
                return false;
            }

            $mail = $this->createMailer();

            // Config SMTP (copié de sendWelcomeEmail, normalement on devrait factoriser)
            $mail->isSMTP();
            $mail->Host = $this->config['mail']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['mail']['user'];
            $mail->Password = $this->config['mail']['pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            // Workaround SSL dev
            if ($this->config['mail']['host'] === 'sandbox.smtp.mailtrap.io') {
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
            }

            $mail->setFrom($this->config['mail']['from'], 'Vite & Gourmand');
            $mail->addAddress($email, $firstName);

            $mail->isHTML(true);
            $mail->Subject = '🔒 Réinitialisation de votre mot de passe';

            // Lien de réinitialisation (A adapter selon URL frontend)
            // ex: http://localhost:5173/reset-password?token=XYZ
            // On peut mettre une valeur par défaut ou la prendre de la config
            $frontendUrl = $this->config['app_url'] ?? 'http://localhost:5173'; 
            $resetLink = "{$frontendUrl}/reset-password?token={$token}";

            // Charger le template HTML
            $templatePath = __DIR__ . '/../../templates/emails/password_reset.html';
            if (!file_exists($templatePath)) {
                // Fallback si le template n'existe pas (pour éviter de bloquer l'envoi)
                $this->logger->warning('Template password_reset introuvable, utilisation fallback', ['path' => $templatePath]);
                $mail->Body = "Bonjour {$firstName},<br><br>Pour réinitialiser votre mot de passe, cliquez ici : <a href='{$resetLink}'>{$resetLink}</a>";
            } else {
                $htmlBody = file_get_contents($templatePath);
                $htmlBody = str_replace('{firstName}', htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'), $htmlBody);
                $htmlBody = str_replace('{resetLink}', $resetLink, $htmlBody); // Le lien est sûr on peut l'injecter direct ou htmlspecialchars selon le cas
                $mail->Body = $htmlBody;
            }

            $mail->AltBody = "Bonjour {$firstName},\n\n"
                . "Pour réinitialiser votre mot de passe, visitez : {$resetLink}\n\n"
                . "Ce lien expire dans 1 heure.";

            $mail->send();
            $this->logger->info('Email de reset envoyé', ['email' => $email]);
            return true;

        } catch (Exception $e) {
            $this->logger->error('Erreur envoi email reset', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
