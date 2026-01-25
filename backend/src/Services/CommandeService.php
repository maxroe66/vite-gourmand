<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Menu;
use App\Repositories\CommandeRepository;
use App\Repositories\MenuRepository;
use App\Exceptions\CommandeException;
use Exception;
use MongoDB\Client as MongoDBClient;

class CommandeService
{
    private CommandeRepository $commandeRepository;
    private MenuRepository $menuRepository;
    private MailerService $mailerService;
    private GoogleMapsService $googleMapsService;
    private UserService $userService;
    private ?MongoDBClient $mongoDBClient;
    private string $mongoDbName;

    public function __construct(
        CommandeRepository $commandeRepository,
        MenuRepository $menuRepository,
        MailerService $mailerService,
        GoogleMapsService $googleMapsService,
        UserService $userService,
        string $mongoDbName,
        ?MongoDBClient $mongoDBClient = null
    ) {
        $this->commandeRepository = $commandeRepository;
        $this->menuRepository = $menuRepository;
        $this->mailerService = $mailerService;
        $this->googleMapsService = $googleMapsService;
        $this->userService = $userService;
        $this->mongoDbName = $mongoDbName; 
        $this->mongoDBClient = $mongoDBClient;
    }

    /**
     * Récupère les détails d'une commande avec sa timeline pour l'affichage.
     * Vérifie que l'utilisateur est autorisé.
     */
    public function getOrderWithTimeline(int $userId, int $commandeId): array
    {
        $commande = $this->commandeRepository->findById($commandeId);
        
        if (!$commande) {
            throw CommandeException::notFound($commandeId);
        }

        // Vérification autorisation
        if ($commande->userId !== $userId) {
             throw new CommandeException("Accès refusé à cette commande.", 403);
        }

        $timeline = $this->commandeRepository->getTimeline($commandeId);
        $materiels = $this->commandeRepository->getMateriels($commandeId);

        return [
            'commande' => $commande,
            'timeline' => array_map(function($event) {
                return [
                    'statut' => $event['statut'],
                    'date' => $event['date_changement'],
                    'commentaire' => $event['commentaire'],
                    'acteur' => ($event['prenom'] ?? 'Système'),
                    'role' => $event['role'] ?? 'SYSTEME'
                ];
            }, $timeline),
            'materiels' => $materiels,
            'actions' => $this->getAvailableActions($commande)
        ];
    }
    
    private function getAvailableActions(Commande $commande): array
    {
        $actions = [];
        if ($commande->statut === Commande::STATUS_EN_ATTENTE) {
            $actions[] = 'annuler';
            $actions[] = 'modifier';
        }
        if ($commande->canBeReviewed()) {
            $actions[] = 'donner_avis';
        }
        return $actions;
    }

    /**
     * Calcule le prix total d'une commande potentielle.
     * @return array [prixMenu, reduction, fraisLivraison, total, details]
     * @throws CommandeException
     */
    public function calculatePrice(int $menuId, int $nombrePersonnes, string $adresseLivraison): array
    {
        $menu = $this->menuRepository->findEntityById($menuId);
        if (!$menu) {
            throw CommandeException::menuNotFound($menuId);
        }

        // RG : Check quantité minimum
        if ($nombrePersonnes < $menu->nombrePersonneMin) {
            throw CommandeException::invalidQuantity($nombrePersonnes, $menu->nombrePersonneMin);
        }

        // 1. Prix de base
        $prixMenuTotal = $menu->prix * $nombrePersonnes;

        // 2. Réduction (RG3 : -10% si 5 personnes de plus que le min)
        $montantReduction = 0.0;
        $reductionAppliquee = false;
        if ($nombrePersonnes >= ($menu->nombrePersonneMin + 5)) {
            $montantReduction = $prixMenuTotal * 0.10;
            $reductionAppliquee = true;
        }

        // 3. Frais de livraison (RG21)
        $distanceKm = $this->googleMapsService->getDistance($adresseLivraison);
        $horsBordeaux = $distanceKm > 0; // Si 0, c'est Bordeaux (selon GoogleMapsService)
        
        $fraisLivraison = 5.00; // Base fixe
        if ($horsBordeaux) {
            $fraisLivraison += (0.59 * $distanceKm);
        }

        // Total
        $total = ($prixMenuTotal - $montantReduction) + $fraisLivraison;

        return [
            'prixMenuUnitaire' => $menu->prix,
            'prixMenuTotal' => $prixMenuTotal,
            'nombrePersonneMinSnapshot' => $menu->nombrePersonneMin,
            'montantReduction' => round($montantReduction, 2),
            'reductionAppliquee' => $reductionAppliquee,
            'distanceKm' => $distanceKm,
            'horsBordeaux' => $horsBordeaux,
            'fraisLivraison' => round($fraisLivraison, 2),
            'prixTotal' => round($total, 2)
        ];
    }

    /**
     * Crée une commande après validation et calcul.
     */
    public function createCommande(int $userId, array $data): int
    {
        // 1. Validation de base
        if (empty($data['menuId']) || empty($data['nombrePersonnes']) || empty($data['adresseLivraison'])) {
            throw new Exception("Données incomplètes.");
        }

        // 2. Calcul du prix (sert aussi de validation métier)
        $pricing = $this->calculatePrice($data['menuId'], $data['nombrePersonnes'], $data['adresseLivraison']);

        // 3. Préparation de l'objet Commande (Snapshot)
        $commandeData = [
            'userId' => $userId,
            'menuId' => $data['menuId'],
            'datePrestation' => $data['datePrestation'],
            'heureLivraison' => $data['heureLivraison'],
            'adresseLivraison' => $data['adresseLivraison'],
            'ville' => $data['ville'],
            'codePostal' => $data['codePostal'],
            'gsm' => $data['gsm'],
            'nombrePersonnes' => $data['nombrePersonnes'],
            
            // Snapshots du calcul
            'nombrePersonneMinSnapshot' => $pricing['nombrePersonneMinSnapshot'],
            'prixMenuUnitaire' => $pricing['prixMenuUnitaire'],
            'montantReduction' => $pricing['montantReduction'],
            'reductionAppliquee' => $pricing['reductionAppliquee'],
            'fraisLivraison' => $pricing['fraisLivraison'],
            'prixTotal' => $pricing['prixTotal'],
            'horsBordeaux' => $pricing['horsBordeaux'],
            'distanceKm' => $pricing['distanceKm'],
            
            // Init flags
            'statut' => 'EN_ATTENTE',
            'hasAvis' => false,
            'materielPret' => false
        ];

        $commande = new Commande($commandeData);

        // 4. Persistence SQL (Transactionnel)
        // Note: Le Repository gère la création et l'historique initial
        // ainsi que la transaction si implémenté, mais ici create est atomique.
        $commandeId = $this->commandeRepository->create($commande);

        // 5. Prêt de Matériel Automatique (Lié au Menu)
        // On récupère le menu avec ses relations (matériel)
        $fullMenuData = $this->menuRepository->findById($data['menuId']);
        if ($fullMenuData && !empty($fullMenuData['materiels'])) {
            $materialsToLoan = [];
            foreach ($fullMenuData['materiels'] as $mat) {
                // RG : La quantité de matériel dépend-elle du nombre de personnes ou c'est forfaitaire ?
                // L'exemple "1 appareil à fondue" est souvent forfaitaire par commande.
                // L'exemple "Vaisselle" serait proportionnel.
                // Dans le doute, l'énoncé est flou, mais la table MENU_MATERIEL a 'quantite'.
                // Supposons que c'est une quantité fixe par menu commandé (ex: 1 menu fondue = 1 appareil).
                // SI on veut multiplier par le nombre de menus commandés ? Ici on commande "UN" type de menu pour N personnes.
                // Si la table de laison diz "1 assiette", et on est 10 personnes, il faut 10 assiettes.
                // Pour simplifier ici, on prend la quantité définie dans MENU_MATERIEL. (ex: 10 Assiettes définies dans le menu).
                
                // TODO Pro: Différencier Matériel Fixe (Fontaine) vs Proportionnel (Couverts).
                // Pour l'instant on prend la valeur brute définie dans le menu.
                
                $materialsToLoan[] = [
                    'id' => $mat['id_materiel'],
                    'quantite' => (int)$mat['quantite']
                ];
            }
            
            if (!empty($materialsToLoan)) {
                $this->loanMaterial($commandeId, $materialsToLoan);
            }
        }

        // 6. Mise à jour Stock Menu (Si le stock est géré par nombre de commandes ?)
        // L'énoncé dit "Stock disponible (par exemple, il reste 5 commande possible de ce menu)"
        // Donc on décrémente de 1 le stock du menu.
        // Note: L'update du matériel se fait automatiquement dans loanMaterial
        $menu = $this->menuRepository->findEntityById($data['menuId']);
        if ($menu && $menu->stockDisponible > 0) {
            $this->menuRepository->decrementStock($menu->id, 1);
        }

        // 7. Sync MongoDB (Analytique - Best Effort)
        // On délègue à la méthode robuste qui va re-fetcher l'objet complet
        $this->syncOrderToStatistics($commandeId);

        // 7. Notification Email
        try {
            // On suppose que MailerService a une méthode sendConfirmation
            // $this->mailerService->sendOrderConfirmation($userId, $commandeId, $pricing);
        } catch (\Exception $e) {
            // On ne bloque pas la commande si l'email échoue, mais on log
            error_log("Email warning: " . $e->getMessage());
        }

        return $commandeId;
    }

    /**
     * Récupère toutes les commandes d'un utilisateur.
     * @return Commande[]
     */
    public function getUserOrders(int $userId): array
    {
        return $this->commandeRepository->findAllByUserId($userId);
    }

    /**
     * Met à jour une commande (Modification Client).
     */
    public function updateCommande(int $userId, int $commandeId, array $data): void
    {
        // 1. Récupérer la commande
        $commande = $this->commandeRepository->findById($commandeId);
        if (!$commande) {
            throw CommandeException::notFound($commandeId);
        }

        // 2. Vérifier que l'utilisateur est bien le propriétaire
        // Note: property is $userId in Model, mapping logic should handle this.
        if ($commande->userId !== $userId) {
            throw new CommandeException("Vous n'êtes pas autorisé à modifier cette commande.", 403);
        }

        // 3. Vérifier le statut (Seulement si pas encore validée/acceptée)
        // Supposons que les statuts soient : EN_ATTENTE, EN_PREPARATION, ACCEPTE, REFUSE, ANNULE...
        // La règle est : "tant qu’un employé n’a pas passé la commande en ACCEPTÉ"
        $blockedStatuses = ['ACCEPTE', 'EN_PREPARATION', 'EN_LIVRAISON', 'TERMINEE', 'LIVREE'];
        if (in_array($commande->statut, $blockedStatuses)) {
             throw new CommandeException("La commande ne peut plus être modifiée car elle a été acceptée ou est en cours.", 403);
        }

        // 4. Si modification de quantités/adresse => Recalcul nécessaire du prix ? 
        // L'énoncé dit "Modification possible sauf menu".
        // Si nbPersonnes ou adresse change, le prix change.
        
        $recalculate = false;
        
        if (isset($data['nombrePersonnes']) && $data['nombrePersonnes'] != $commande->nombrePersonnes) {
            $commande->nombrePersonnes = (int)$data['nombrePersonnes'];
            $recalculate = true;
        }

        if (isset($data['adresseLivraison']) && $data['adresseLivraison'] !== $commande->adresseLivraison) {
            $commande->adresseLivraison = $data['adresseLivraison'];
            $recalculate = true;
        }

        // RG : Annulation Client
        // Le client peut annuler sa commande (passer à ANNULEE) si elle n'est pas bloquée
        // Note: Les statuts bloquants sont déjà vérifiés au début de la méthode (step 3)
        if (isset($data['statut']) && $data['statut'] === 'ANNULEE') {
            $commande->statut = 'ANNULEE';
        }
        // Support pour la clé 'status' aussi (convention API souvent utilisée)
        if (isset($data['status']) && $data['status'] === 'ANNULEE') {
            $commande->statut = 'ANNULEE';
        }

        // Update basic fields
        if (isset($data['datePrestation'])) $commande->datePrestation = $data['datePrestation'];
        if (isset($data['heureLivraison'])) $commande->heureLivraison = $data['heureLivraison'];
        if (isset($data['gsm'])) $commande->gsm = $data['gsm'];
        if (isset($data['codePostal'])) $commande->codePostal = $data['codePostal'];
        if (isset($data['ville'])) $commande->ville = $data['ville'];

        if ($recalculate) {
            // Recalcul du prix via la logique existante
            $pricing = $this->calculatePrice($commande->menuId, $commande->nombrePersonnes, $commande->adresseLivraison);
            $commande->prixTotal = $pricing['prixTotal'];
            // $commande->prixParPersonne = $pricing['details']['prixParPersonne']; // Si stocké
            $commande->fraisLivraison = $pricing['fraisLivraison'];
            $commande->reduction = $pricing['montantReduction'] ?? 0.0;
        }

        // 5. Sauvegarde
        $this->commandeRepository->update($commande);
    }

    /**
     * Enregistre le prêt de matériel pour une commande.
     * Réservé aux employés.
     */
    public function loanMaterial(int $commandeId, array $materiels): void
    {
        // Validation basique
        if (empty($materiels)) {
            throw new Exception("La liste de matériel est vide.");
        }
        foreach ($materiels as $m) {
            if (!isset($m['id']) || !isset($m['quantite'])) {
                throw new Exception("Format matériel invalide.");
            }
        }

        // On pourrait vérifier les stocks ici via MaterielRepository (non chargé ici)
        // Mais le repository CommandeRepository fera l'update et echouera si contrainte CHECK (si base stricte)
        // ou passera en négatif (si pas strict). On assume que l'employé vérifie physiquement.
        
        $this->commandeRepository->setMateriel($commandeId, $materiels);

        // Envoyer email de bon de prêt
        // RG : L'utilisateur doit recevoir la liste de ce qu'il a emprunté
        try {
            $commande = $this->commandeRepository->findById($commandeId);
            if ($commande) {
                $user = $this->userService->getUserById($commande->userId);
                
                // On doit récupérer les noms du matériel pour l'email, car $dat aue ID et Qte
                // On refait un getMateriels via le repository pour avoir les infos complètes 
                // car setMateriel a tout persisté.
                $materielsEmpruntes = $this->commandeRepository->getMateriels($commandeId);
                
                $listHtml = "<ul>";
                foreach ($materielsEmpruntes as $mat) {
                    $nom = htmlspecialchars($mat['libelle'] ?? 'Matériel inconnu');
                    $qty = (int)$mat['quantite'];
                    $listHtml .= "<li>{$qty}x {$nom}</li>";
                }
                $listHtml .= "</ul>";
                
                $firstName = $user['prenom'] ?? 'Client';
                $email = $user['email'] ?? '';
                
                if ($email) {
                    $this->mailerService->sendLoanConfirmation($email, $firstName, $listHtml);
                }
            }
        } catch (\Exception $e) {
             error_log("Email warning (Loan): " . $e->getMessage());
        }
    }

    /**
     * Enregistre le retour du matériel et clôture la commande.
     * @param int $commandeId
     * @param int $employeId
     */
    public function returnMaterial(int $commandeId, int $employeId): void
    {
        // 1. Enregistrer le retour physique (Remet le stock)
        $this->commandeRepository->returnMateriel($commandeId);

        // 2. Passer la commande à TERMINÉE
        $this->updateStatus($employeId, $commandeId, 'TERMINEE', 'Retour matériel validé');

        // Petit délai pour éviter le rate limit SMTP (Mailtrap sandbox)
        usleep(500000); // 0.5 seconde

        // 3. Email de confirmation / remerciement
        try {
            $commande = $this->commandeRepository->findById($commandeId);
            if ($commande) {
                $user = $this->userService->getUserById($commande->userId);
                $email = $user['email'] ?? null;
                $firstName = $user['prenom'] ?? 'Client';
                
                if ($email) {
                    $this->mailerService->sendMaterialReturnConfirmation($email, $firstName);
                }
            }
        } catch (\Exception $e) {
            error_log("Erreur envoi email retour matériel: " . $e->getMessage());
        }
    }

    /**
     * Change le statut d'une commande.
     */
    public function updateStatus(int $userId, int $commandeId, string $newStatus, string $motif = null, string $modeContact = null): void
    {
        // 1. Vérification des droits (pourrait être fait en amont dans Controller via Middleware)
        // Ici on suppose que c'est un Employé ou l'Utilisateur lui-même (pour annulation) appelant.
        // La méthode repository gère l'historique.

        $success = $this->commandeRepository->updateStatus($commandeId, $newStatus, $userId, $motif, $modeContact);

        if ($success) {
            // Règles notifications spécifiques
            
            // RG30 : Alerte retour matériel (Pénalité 600€)
            if ($newStatus === 'EN_ATTENTE_RETOUR') {
                try {
                    // Récupérer infos user pour email
                    $commande = $this->commandeRepository->findById($commandeId);
                    if ($commande && isset($commande->userId)) {
                        $user = $this->userService->getUserById($commande->userId);
                        $email = $user['email'] ?? null;
                        $firstName = $user['prenom'] ?? 'Client';
                        
                        if ($email) {
                            $this->mailerService->sendMaterialReturnAlert($email, $firstName);
                        }
                    }
                } catch (\Exception $e) {
                    error_log("Erreur envoi email retour matériel (Alerte): " . $e->getMessage());
                }
            }
            
            // RG31 : Invitation à donner un avis (envoyer un email au client)
            // Ne pas envoyer si c'est un retour matériel (l'email de confirmation retour sera envoyé à la place)
            if ($newStatus === 'TERMINEE' && $motif !== 'Retour matériel validé') {
                try {
                    $commande = $this->commandeRepository->findById($commandeId);
                    if ($commande && isset($commande->userId)) {
                        $user = $this->userService->getUserById($commande->userId);
                        if ($user && !empty($user['email'])) {
                            $firstName = $user['prenom'] ?? ($user['firstName'] ?? 'Client');
                            $this->mailerService->sendReviewAvailableEmail($user['email'], $firstName, $commandeId);
                        }
                    }
                } catch (\Exception $e) {
                     error_log("Erreur envoi email avis: " . $e->getMessage());
                }
            }

            // Sync status update to MongoDB
            $this->syncOrderToStatistics($commandeId);
        }
    }
    
    /**
     * Synchronise l'état complet de la commande vers MongoDB (Upsert).
     * Récupère la source de vérité depuis MySQL pour assurer la cohérence.
     */
    private function syncOrderToStatistics(int $commandeId): void
    {
        $logPrefix = "[MongoDB Sync #$commandeId]";
        
        if (!$this->mongoDBClient) {
            error_log("$logPrefix ERREUR: mongoDBClient est NULL - MongoDB non configuré");
            return;
        }

        try {
            error_log("$logPrefix Début de la synchronisation");
            
            // 1. Récupérer la commande fraîche depuis SQL (Source de vérité)
            $commande = $this->commandeRepository->findById($commandeId);
            
            // Si jamais la commande n'existe plus (supprimée?), on ne fait rien ou on devrait supprimer de Mongo aussi?
            // Pour l'instant, on ignore.
            if (!$commande) {
                error_log("$logPrefix ATTENTION: Commande non trouvée dans MySQL");
                return;
            }

            error_log("$logPrefix Commande récupérée - Tentative de connexion à MongoDB (DB: {$this->mongoDbName})");
            
            $collection = $this->mongoDBClient->selectCollection($this->mongoDbName, 'statistiques_commandes');
            
            // 2. Préparer le document complet
            // On s'assure d'avoir une date de commande valide
            $dateCommande = $commande->dateCommande ?? date('Y-m-d H:i:s');

            $document = [
                'commandeId' => (int)$commande->id,
                'menuId' => (int)$commande->menuId,
                'nombrePersonnes' => (int)$commande->nombrePersonnes,
                'prixTotal' => (float)$commande->prixTotal,
                'dateCommande' => $dateCommande,
                'status' => $commande->statut, // ex: TERMINEE
                
                // Champs analytiques
                'ville' => $commande->ville,
                'horsBordeaux' => (bool)$commande->horsBordeaux,
                
                // Metadonnées de sync
                'updatedAt' => date('Y-m-d H:i:s')
            ];

            error_log("$logPrefix Document préparé - Exécution de l'upsert...");
            error_log("$logPrefix Filter: commandeId={$commande->id} (type: " . gettype($commande->id) . ")");
            error_log("$logPrefix Document: " . json_encode($document));

            // 3. Upsert (Update or Insert)  
            // IMPORTANT: Retirer _id du document pour éviter E11000 duplicate key error
            // MongoDB génère automatiquement l'_id lors de l'insertion
            unset($document['_id']);
            
            $filter = ['commandeId' => (int)$commande->id];
            $result = $collection->updateOne(
                $filter,
                ['$set' => $document],
                ['upsert' => true]
            );

            $matchedCount = $result->getMatchedCount();
            $modifiedCount = $result->getModifiedCount();
            $upsertedId = $result->getUpsertedId();
            
            error_log("$logPrefix SUCCÈS - Matched: $matchedCount, Modified: $modifiedCount, UpsertedId: " . ($upsertedId ? json_encode($upsertedId) : 'NONE'));
            error_log("$logPrefix Action effectuée: " . ($matchedCount > 0 ? 'UPDATE' : 'INSERT'));

        } catch (\MongoDB\Driver\Exception\AuthenticationException $e) {
            error_log("$logPrefix ERREUR AUTHENTIFICATION MongoDB: " . $e->getMessage());
            error_log("$logPrefix Vérifiez MONGO_USERNAME et MONGO_PASSWORD dans Azure");
        } catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
            error_log("$logPrefix ERREUR TIMEOUT MongoDB: " . $e->getMessage());
            error_log("$logPrefix Vérifiez MONGO_HOST, MONGO_PORT et la connectivité réseau");
        } catch (\MongoDB\Driver\Exception\ConnectionException $e) {
            error_log("$logPrefix ERREUR CONNEXION MongoDB: " . $e->getMessage());
            error_log("$logPrefix URI utilisée: " . (defined('MONGO_URI_LOG') ? MONGO_URI_LOG : 'non disponible'));
        } catch (\Exception $e) {
            error_log("$logPrefix ERREUR GÉNÉRALE: " . $e->getMessage());
            error_log("$logPrefix Trace: " . $e->getTraceAsString());
        }
    }

    /**
     * Recherche de commandes pour les employés.
     */
    public function searchCommandes(array $filters): array
    {
        return $this->commandeRepository->findByFilters($filters);
    }
}
