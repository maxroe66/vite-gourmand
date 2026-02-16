<?php

namespace App\Services;

use App\Repositories\ContactRepository;
use App\Validators\ContactValidator;
use Psr\Log\LoggerInterface;

/**
 * Service métier pour la gestion des messages de contact.
 * Orchestre la validation, la persistance et l'envoi d'email.
 */
class ContactService
{
    private ContactRepository $contactRepository;
    private ContactValidator $contactValidator;
    private MailerService $mailerService;
    private LoggerInterface $logger;

    public function __construct(
        ContactRepository $contactRepository,
        ContactValidator $contactValidator,
        MailerService $mailerService,
        LoggerInterface $logger
    ) {
        $this->contactRepository = $contactRepository;
        $this->contactValidator = $contactValidator;
        $this->mailerService = $mailerService;
        $this->logger = $logger;
    }

    /**
     * Traite la soumission d'un message de contact.
     *
     * 1. Valide les données entrantes
     * 2. Sauvegarde le message en base de données
     * 3. Envoie un email de notification à l'entreprise
     *
     * @param array $data Données du formulaire (titre, email, description)
     * @return array ['success' => bool, 'message' => string, 'errors' => ?array]
     */
    public function submitContact(array $data): array
    {
        // 1. Validation
        $validation = $this->contactValidator->validate($data);
        if (!$validation['isValid']) {
            return [
                'success' => false,
                'errors' => $validation['errors']
            ];
        }

        $titre = trim($data['titre']);
        $email = trim($data['email']);
        $description = trim($data['description']);

        // 2. Persistance en base de données
        try {
            $contactId = $this->contactRepository->create($titre, $description, $email);
            $this->logger->info('Message de contact enregistré', [
                'id' => $contactId,
                'email' => $email,
                'titre' => $titre
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'enregistrement du message de contact', [
                'error' => $e->getMessage(),
                'email' => $email
            ]);
            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'envoi de votre message. Veuillez réessayer.'
            ];
        }

        // 3. Envoi de l'email de notification à l'entreprise
        try {
            $this->mailerService->sendContactNotification($email, $titre, $description);
        } catch (\Exception $e) {
            // L'email n'est pas bloquant : le message est déjà sauvegardé en BDD.
            // L'admin pourra le consulter dans la base de données.
            $this->logger->warning('Email de notification contact non envoyé', [
                'contactId' => $contactId,
                'error' => $e->getMessage()
            ]);
        }

        return [
            'success' => true,
            'message' => 'Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.'
        ];
    }
}
