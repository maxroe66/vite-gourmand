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
    private ?MongoDBClient $mongoDBClient;

    public function __construct(
        CommandeRepository $commandeRepository,
        MenuRepository $menuRepository,
        MailerService $mailerService,
        GoogleMapsService $googleMapsService,
        ?MongoDBClient $mongoDBClient = null
    ) {
        $this->commandeRepository = $commandeRepository;
        $this->menuRepository = $menuRepository;
        $this->mailerService = $mailerService;
        $this->googleMapsService = $googleMapsService;
        $this->mongoDBClient = $mongoDBClient;
    }

    /**
     * Calcule le prix total d'une commande potentielle.
     * @return array [prixMenu, reduction, fraisLivraison, total, details]
     * @throws CommandeException
     */
    public function calculatePrice(int $menuId, int $nombrePersonnes, string $adresseLivraison): array
    {
        $menu = $this->menuRepository->findById($menuId);
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

        // 5. Mise à jour Stock Menu (Si le stock est géré par nombre de commandes ?)
        // L'énoncé dit "Stock disponible (par exemple, il reste 5 commande possible de ce menu)"
        // Donc on décrémente de 1 le stock du menu.
        $menu = $this->menuRepository->findById($data['menuId']);
        if ($menu->stockDisponible > 0) {
            $this->menuRepository->updateStock($menu->id, $menu->stockDisponible - 1);
        }

        // 6. Sync MongoDB (Analytique - Best Effort)
        $this->syncToMongoDB($commandeId, $commandeData);

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
     * Change le statut d'une commande.
     */
    public function updateStatus(int $userId, int $commandeId, string $newStatus, string $motif = null, string $modeContact = null): void
    {
        // 1. Vérification des droits (pourrait être fait en amont dans Controller via Middleware)
        // Ici on suppose que c'est un Employé ou l'Utilisateur lui-même (pour annulation) appelant.
        // La méthode repository gère l'historique.

        $success = $this->commandeRepository->updateStatus($commandeId, $newStatus, $userId, $motif, $modeContact);

        if ($success) {
            // Sync status update to MongoDB
            if ($this->mongoDBClient) {
                try {
                    $collection = $this->mongoDBClient->selectCollection('vite_et_gourmand', 'statistiques_commandes');
                    $collection->updateOne(
                        ['commandeId' => $commandeId],
                        ['$set' => ['status' => $newStatus, 'updatedAt' => date('Y-m-d H:i:s')]]
                    );
                } catch (\Exception $e) {
                    // Ignore Mongo error
                }
            }
            
            // Trigger emails based on status
            // if ($newStatus === 'TERMINEE') ... inviter à donner avis
        }
    }
    
    /**
     * Synchronisation vers MongoDB pour les stats.
     */
    private function syncToMongoDB(int $commandeId, array $data): void
    {
        if (!$this->mongoDBClient) return;

        try {
            $collection = $this->mongoDBClient->selectCollection('vite_et_gourmand', 'statistiques_commandes');
            $collection->insertOne([
                'commandeId' => $commandeId,
                'menuId' => $data['menuId'],
                'nombrePersonnes' => $data['nombrePersonnes'],
                'prixTotal' => $data['prixTotal'],
                'dateCommande' => date('Y-m-d H:i:s'),
                'status' => 'EN_ATTENTE',
                // Données anonymisées pour stats
                'ville' => $data['ville'],
                'horsBordeaux' => $data['horsBordeaux']
            ]);
        } catch (\Exception $e) {
            // Best effort : on ne fail pas la transaction SQL si Mongo plante
            error_log("MongoDB Sync Error: " . $e->getMessage());
        }
    }
}
