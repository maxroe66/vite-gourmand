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

    /**
     * Envoie l'email de notification à l'utilisateur pour l'inviter à laisser un avis
     * @param string $email
     * @param string $firstName
     * @param int $commandeId
     * @return bool
     */
    public function sendReviewAvailableEmail(string $email, string $firstName, int $commandeId): bool
    {
        try {
            if (empty($this->config['mail']['host'])) {
                $this->logger->warning('Configuration SMTP manquante, email review non envoyé', ['email' => $email]);
                return false;
            }

            $mail = $this->createMailer();
            $mail->isSMTP();
            $mail->Host = $this->config['mail']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['mail']['user'];
            $mail->Password = $this->config['mail']['pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            if ($this->config['mail']['host'] === 'sandbox.smtp.mailtrap.io') {
                $mail->SMTPOptions = [
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]
                ];
            }

            $mail->setFrom($this->config['mail']['from'], 'Vite & Gourmand');
            $mail->addAddress($email, $firstName);

            $mail->isHTML(true);
            $mail->Subject = '⭐ Donnez votre avis sur votre commande';

            $frontendUrl = $this->config['app_url'] ?? 'http://localhost:5173';
            // Lien direct vers la page Profil avec l'ID de la commande et un fragment utile pour le scroll/identifiant
            // Pointer vers la page profil statique (chemin utilisé par le frontend)
            $orderLink = rtrim($frontendUrl, '/') . '/frontend/pages/profil.html?orderId=' . $commandeId . '#order-' . $commandeId;

            $templatePath = __DIR__ . '/../../templates/emails/review_available.html';
            if (!file_exists($templatePath)) {
                $this->logger->warning('Template email review introuvable, utilisation fallback', ['path' => $templatePath]);
                $mail->Body = "Bonjour {$firstName},<br><br>Votre commande est terminée. Vous pouvez laisser un avis en visitant : <a href='{$orderLink}'>{$orderLink}</a>";
            } else {
                $htmlBody = file_get_contents($templatePath);
                $htmlBody = str_replace('{firstName}', htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'), $htmlBody);
                $htmlBody = str_replace('{orderLink}', $orderLink, $htmlBody);
                $mail->Body = $htmlBody;
            }

            $mail->AltBody = "Bonjour {$firstName},\n\nVotre commande est terminée. Pour laisser un avis, visitez : {$orderLink}";

            $mail->send();
            $this->logger->info('Email review envoyé', ['email' => $email, 'commandeId' => $commandeId]);
            return true;

        } catch (Exception $e) {
            $this->logger->error('Erreur envoi email review', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Envoie l'email de notification de création de compte employé
     * @param string $email
     * @param string $firstName
     * @return bool
     */
    public function sendEmployeeAccountCreated(string $email, string $firstName): bool
    {
        try {
            if (empty($this->config['mail']['host'])) {
                $this->logger->warning('Configuration SMTP manquante', ['email' => $email]);
                return false;
            }

            $mail = $this->createMailer();
            $mail->isSMTP();
            $mail->Host = $this->config['mail']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['mail']['user'];
            $mail->Password = $this->config['mail']['pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            if ($this->config['mail']['host'] === 'sandbox.smtp.mailtrap.io') {
                $mail->SMTPOptions = [
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]
                ];
            }

            $mail->setFrom($this->config['mail']['from'], 'Vite & Gourmand RH');
            $mail->addAddress($email, $firstName);

            $mail->isHTML(true);
            $mail->Subject = '💼 Vite & Gourmand - Votre compte employé est prêt';

            $templatePath = __DIR__ . '/../../templates/emails/employee_welcome.html';
            if (file_exists($templatePath)) {
                $htmlBody = file_get_contents($templatePath);
                $htmlBody = str_replace(['{firstName}', '{email}'], [htmlspecialchars($firstName), htmlspecialchars($email)], $htmlBody);
                $mail->Body = $htmlBody;
            } else {
                $mail->Body = "Bonjour $firstName, votre compte employé a été créé. Identifiant: $email. Demandez votre mot de passe à l'admin.";
            }

            $mail->AltBody = "Bonjour $firstName,\n\nVotre compte employé a été créé.\nIdentifiant: $email\n\nMerci de contacter l'administrateur pour obtenir votre mot de passe.\n\nL'équipe Vite & Gourmand";

            $mail->send();
            $this->logger->info('Email employé envoyé', ['email' => $email]);
            return true;

        } catch (Exception $e) {
            $this->logger->error("Erreur envoi email employé: {$e->getMessage()}", ['email' => $email]);
            return false;
        }
    }

    /**
     * Envoie l'email de confirmation de commande
     * @param string $email
     * @param string $firstName
     * @param string $orderSummary
     * @return bool
     */
    public function sendOrderConfirmation(string $email, string $firstName, string $orderSummary): bool
    {
        try {
            if (empty($this->config['mail']['host'])) {
                $this->logger->warning('Configuration SMTP manquante', ['email' => $email]);
                return false;
            }

            $mail = $this->createMailer();
            $mail->isSMTP();
            $mail->Host = $this->config['mail']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['mail']['user'];
            $mail->Password = $this->config['mail']['pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            if ($this->config['mail']['host'] === 'sandbox.smtp.mailtrap.io') {
                $mail->SMTPOptions = [
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]
                ];
            }

            $mail->setFrom($this->config['mail']['from'], 'Vite & Gourmand');
            $mail->addAddress($email, $firstName);

            $mail->isHTML(true);
            $mail->Subject = '🍽️ Vite & Gourmand - Confirmation de votre commande';

            $templatePath = __DIR__ . '/../../templates/emails/confirm_order.html';
            if (file_exists($templatePath)) {
                $htmlBody = file_get_contents($templatePath);
                $htmlBody = str_replace(['{firstName}', '{orderSummary}'], [htmlspecialchars($firstName), $orderSummary], $htmlBody);
                $mail->Body = $htmlBody;
            } else {
                $mail->Body = "Bonjour $firstName,\n\nVotre commande a été confirmée.\n\nDétails:\n$orderSummary\n\nMerci de votre confiance.\n\nL'équipe Vite & Gourmand";
            }

            $mail->AltBody = "Bonjour $firstName,\n\nVotre commande a été confirmée.\n\nDétails:\n$orderSummary\n\nMerci de votre confiance.\n\nL'équipe Vite & Gourmand";

            $mail->send();
            $this->logger->info('Email confirmation commande envoyé', ['email' => $email]);
            return true;

        } catch (Exception $e) {
            $this->logger->error("Erreur envoi email confirmation commande: {$e->getMessage()}", ['email' => $email]);
            return false;
        }
    }

    /**
     * Envoie le bon de prêt de matériel
     * @param string $email
     * @param string $firstName
     * @param string $materialHtmlList Liste HTML (<ul>...</ul>) du matériel
     * @return bool
     */
    public function sendLoanConfirmation(string $email, string $firstName, string $materialHtmlList): bool
    {
        try {
            if (empty($this->config['mail']['host'])) return false;

            $mail = $this->createMailer();
            
            $mail->isSMTP();
            $mail->Host = $this->config['mail']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['mail']['user'];
            $mail->Password = $this->config['mail']['pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            
            if ($this->config['mail']['host'] === 'sandbox.smtp.mailtrap.io') {
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
            }

            $mail->setFrom($this->config['mail']['from'], 'Vite & Gourmand - Service Matériel');
            $mail->addAddress($email, $firstName);

            $mail->isHTML(true);
            $mail->Subject = '📦 Votre Bon de Prêt de Matériel';

            $templatePath = __DIR__ . '/../../templates/emails/material_loan.html';
            
            if (file_exists($templatePath)) {
                $htmlBody = file_get_contents($templatePath);
                $htmlBody = str_replace('{firstName}', htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'), $htmlBody);
                $htmlBody = str_replace('{materialList}', $materialHtmlList, $htmlBody); // On suppose le HTML safe généré par le Service
                $mail->Body = $htmlBody;
            } else {
                $mail->Body = "Bonjour $firstName,<br>Voici le matériel prêté : $materialHtmlList <br>Attention à la caution de 600€.";
            }

            $mail->AltBody = "Bonjour $firstName,\nVoici le matériel prêté :\n" . strip_tags($materialHtmlList) . "\n\nAttention: non restitution sous 10 jours = 600€ de frais.";

            $mail->send();
            $this->logger->info('Email bon de prêt envoyé', ['email' => $email]);
            return true;

        } catch (Exception $e) {
            $this->logger->error("Erreur envoi bon de prêt: {$e->getMessage()}", ['email' => $email]);
            return false;
        }
    }

    /**
     * Envoie l'alerte de retour matériel (Caution)
     * @param string $email
     * @param string $firstName
     * @return bool
     */
    public function sendMaterialReturnAlert(string $email, string $firstName): bool
    {
        try {
            if (empty($this->config['mail']['host'])) return false;

            $mail = $this->createMailer();
            $mail->isSMTP();
            $mail->Host = $this->config['mail']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['mail']['user'];
            $mail->Password = $this->config['mail']['pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            
            if ($this->config['mail']['host'] === 'sandbox.smtp.mailtrap.io') {
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
            }

            $mail->setFrom($this->config['mail']['from'], 'Vite & Gourmand - SAV');
            $mail->addAddress($email, $firstName);

            $mail->isHTML(true);
            $mail->Subject = '⚠️ ALERTE : Retour Matériel & Caution';

            $templatePath = __DIR__ . '/../../templates/emails/material_return_alert.html';
            
            if (file_exists($templatePath)) {
                $htmlBody = file_get_contents($templatePath);
                $htmlBody = str_replace('{firstName}', htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'), $htmlBody);
                $mail->Body = $htmlBody;
            } else {
                $mail->Body = "Bonjour $firstName,<br>URGENT: Merci de retourner le matériel sous 10 jours pour éviter 600€ de pénalités.";
            }

            $mail->AltBody = "Bonjour $firstName,\nURGENT: Merci de retourner le matériel sous 10 jours pour éviter 600€ de pénalités.";

            $mail->send();
            $this->logger->info('Email alerte retour envoyé', ['email' => $email]);
            return true;

        } catch (Exception $e) {
            $this->logger->error("Erreur envoi alerte retour: {$e->getMessage()}", ['email' => $email]);
            return false;
        }
    }

    /**
     * Envoie la confirmation de retour (Clôture)
     * @param string $email
     * @param string $firstName
     * @return bool
     */
    public function sendMaterialReturnConfirmation(string $email, string $firstName): bool
    {
        try {
            if (empty($this->config['mail']['host'])) return false;

            $mail = $this->createMailer();
            $mail->isSMTP();
            $mail->Host = $this->config['mail']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['mail']['user'];
            $mail->Password = $this->config['mail']['pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            
            if ($this->config['mail']['host'] === 'sandbox.smtp.mailtrap.io') {
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
            }

            $mail->setFrom($this->config['mail']['from'], 'Vite & Gourmand');
            $mail->addAddress($email, $firstName);

            $mail->isHTML(true);
            $mail->Subject = '✅ Retour Matériel Confirmé';

            $templatePath = __DIR__ . '/../../templates/emails/material_return_confirmation.html';
            
            if (file_exists($templatePath)) {
                $htmlBody = file_get_contents($templatePath);
                $htmlBody = str_replace('{firstName}', htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'), $htmlBody);
                $mail->Body = $htmlBody;
            } else {
                $mail->Body = "Bonjour $firstName,<br>Votre matériel a bien été réceptionné. Tout est en ordre. Merci !";
            }

            $mail->AltBody = "Bonjour $firstName,\nVotre matériel a bien été réceptionné. Tout est en ordre. Merci !";

            $mail->send();
            $this->logger->info('Email confirmation retour envoyé', ['email' => $email]);
            return true;

        } catch (Exception $e) {
            $this->logger->error("Erreur envoi conf retour: {$e->getMessage()}", ['email' => $email]);
            return false;
        }
    }

    /**
     * Envoie un email de notification de contact à l'entreprise.
     * Appelé lorsqu'un visiteur soumet le formulaire de contact.
     *
     * @param string $senderEmail Email du visiteur (utilisé en Reply-To)
     * @param string $titre       Titre / objet du message
     * @param string $description Contenu du message
     * @return bool
     */
    public function sendContactNotification(string $senderEmail, string $titre, string $description): bool
    {
        try {
            if (empty($this->config['mail']['host']) || empty($this->config['mail']['user'])) {
                $this->logger->warning('Configuration SMTP manquante, email contact non envoyé', [
                    'senderEmail' => $senderEmail
                ]);
                return false;
            }

            $mail = $this->createMailer();

            $mail->isSMTP();
            $mail->Host = $this->config['mail']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['mail']['user'];
            $mail->Password = $this->config['mail']['pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            if ($this->config['mail']['host'] === 'sandbox.smtp.mailtrap.io') {
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
            }

            // Expéditeur = adresse no-reply de l'entreprise (pour éviter le spam)
            $mail->setFrom($this->config['mail']['from'], 'Vite & Gourmand — Contact');
            // Reply-To = adresse du visiteur pour faciliter la réponse
            $mail->addReplyTo($senderEmail);
            // Destinataire = adresse de contact de l'entreprise
            $contactEmail = $this->config['mail']['contact_email'] ?? $this->config['mail']['from'];
            $mail->addAddress($contactEmail, 'Vite & Gourmand');

            $mail->isHTML(true);
            $mail->Subject = '📩 Nouveau message de contact — ' . mb_substr($titre, 0, 80);

            // Charger le template HTML
            $templatePath = __DIR__ . '/../../templates/emails/contact_notification.html';
            if (file_exists($templatePath)) {
                $htmlBody = file_get_contents($templatePath);
                $htmlBody = str_replace(
                    ['{senderEmail}', '{titre}', '{description}'],
                    [
                        htmlspecialchars($senderEmail, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($titre, ENT_QUOTES, 'UTF-8'),
                        nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8'))
                    ],
                    $htmlBody
                );
                $mail->Body = $htmlBody;
            } else {
                $mail->Body = "<h2>Nouveau message de contact</h2>"
                    . "<p><strong>De :</strong> " . htmlspecialchars($senderEmail) . "</p>"
                    . "<p><strong>Objet :</strong> " . htmlspecialchars($titre) . "</p>"
                    . "<hr>"
                    . "<p>" . nl2br(htmlspecialchars($description)) . "</p>";
            }

            $mail->AltBody = "Nouveau message de contact\n\n"
                . "De : {$senderEmail}\n"
                . "Objet : {$titre}\n\n"
                . "Message :\n{$description}\n";

            $mail->send();
            $this->logger->info('Email de notification contact envoyé', [
                'senderEmail' => $senderEmail,
                'titre' => $titre
            ]);
            return true;

        } catch (Exception $e) {
            $this->logger->error("Erreur envoi email contact: {$e->getMessage()}", [
                'senderEmail' => $senderEmail
            ]);
            return false;
        }
    }
}