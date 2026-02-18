<?php

namespace App\Repositories;

use App\Models\Commande;
use PDO;

class CommandeRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Met à jour les informations d'une commande existante.
     */
    public function update(Commande $commande): bool
    {
        $sql = "UPDATE COMMANDE SET 
                date_prestation = :datePrestation,
                heure_livraison = :heureLivraison,
                adresse_livraison = :adresseLivraison,
                ville = :ville,
                code_postal = :codePostal,
                gsm = :gsm,
                nombre_personnes = :nombrePersonnes,
                prix_total = :prixTotal,
                statut = :status,
                frais_livraison = :fraisLivraison,
                montant_reduction = :reduction
                WHERE id_commande = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':datePrestation' => $commande->datePrestation,
            ':heureLivraison' => $commande->heureLivraison,
            ':adresseLivraison' => $commande->adresseLivraison,
            ':ville' => $commande->ville,
            ':codePostal' => $commande->codePostal,
            ':gsm' => $commande->gsm,
            ':nombrePersonnes' => $commande->nombrePersonnes,
            ':prixTotal' => $commande->prixTotal,
            ':status' => $commande->statut, // Correct property
            ':fraisLivraison' => $commande->fraisLivraison ?? 0,
            ':reduction' => $commande->montantReduction ?? 0,
            ':id' => $commande->id
        ]);
    }

    /**
     * Crée une nouvelle commande et initialise son statut.
     * @param Commande $commande
     * @return int ID de la commande créée
     */
    public function create(Commande $commande): int
    {
        // 1. Insertion dans la table COMMANDE
        $sql = "INSERT INTO COMMANDE (
            id_utilisateur, id_menu, date_prestation, heure_livraison, 
            adresse_livraison, ville, code_postal, gsm, 
            nombre_personnes, nombre_personne_min_snapshot, prix_menu_unitaire, 
            montant_reduction, reduction_appliquee, frais_livraison, prix_total, 
            hors_bordeaux, distance_km, statut, has_avis, materiel_pret
        ) VALUES (
            :userId, :menuId, :datePrestation, :heureLivraison, 
            :adresseLivraison, :ville, :codePostal, :gsm, 
            :nombrePersonnes, :nombrePersonneMinSnapshot, :prixMenuUnitaire, 
            :montantReduction, :reductionAppliquee, :fraisLivraison, :prixTotal, 
            :horsBordeaux, :distanceKm, :statut, :hasAvis, :materielPret
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'userId' => $commande->userId,
            'menuId' => $commande->menuId,
            'datePrestation' => $commande->datePrestation,
            'heureLivraison' => $commande->heureLivraison,
            'adresseLivraison' => $commande->adresseLivraison,
            'ville' => $commande->ville,
            'codePostal' => $commande->codePostal,
            'gsm' => $commande->gsm,
            'nombrePersonnes' => $commande->nombrePersonnes,
            'nombrePersonneMinSnapshot' => $commande->nombrePersonneMinSnapshot,
            'prixMenuUnitaire' => $commande->prixMenuUnitaire,
            'montantReduction' => $commande->montantReduction,
            'reductionAppliquee' => $commande->reductionAppliquee ? 1 : 0,
            'fraisLivraison' => $commande->fraisLivraison,
            'prixTotal' => $commande->prixTotal,
            'horsBordeaux' => $commande->horsBordeaux ? 1 : 0,
            'distanceKm' => $commande->distanceKm,
            'statut' => $commande->statut, // Devrait être 'EN_ATTENTE' par défaut
            'hasAvis' => $commande->hasAvis ? 1 : 0,
            'materielPret' => $commande->materielPret ? 1 : 0,
        ]);

        $commandeId = (int) $this->pdo->lastInsertId();

        // 2. Initialisation dans COMMANDE_STATUT (Traçabilité création)
        $this->addHistory($commandeId, $commande->statut, $commande->userId, 'Création de la commande');

        return $commandeId;
    }

    /**
     * Trouve une commande par ID avec les jointures nécessaires.
     */
    public function findById(int $id): ?Commande
    {
        $sql = "SELECT * FROM COMMANDE WHERE id_commande = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        // Mapping des colonnes DB vers les propriétés du Model Commande
        // Note: Le constructeur de Commande attend un tableau pour hydrater
        // On doit transformer les clés snake_case en camelCase si le Model l'attend
        // D'après Commande.php (lu précédemment), il utilise une méthode hydrate.
        // On va assumer que hydrate gère ce mapping ou qu'on passe les bonnes clés.
        // Pour l'instant on passe les données brutes, il faudra s'assurer que Commande::hydrate gère le mapping.
        // Si Commande::hydrate est simple, on devra mapper ici.
        // Vérifions Commande.php... Hydrate parcourt le tableau.
        // Mes propriétés sont camelCase (userId, menuId...)
        // Les colonnes sont snake_case (id_utilisateur, id_menu...)
        // Il faut un mapping.
        
        return new Commande($this->mapToModel($data));
    }

    /**
     * Trouve toutes les commandes d'un utilisateur.
     */
    public function findAllByUserId(int $userId): array
    {
        $sql = "SELECT * FROM COMMANDE WHERE id_utilisateur = :userId ORDER BY date_commande DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $commandes = [];
        foreach ($rows as $row) {
            $commandes[] = new Commande($this->mapToModel($row));
        }

        return $commandes;
    }

    /**
     * Met à jour le statut d'une commande et loggue l'historique.
     * Pour une annulation, utilise COMMANDE_ANNULATION si modeContact fourni.
     */
    public function updateStatus(int $id, string $newStatus, int $modifiedBy, string $motif = null, string $modeContact = null): bool
    {
        $this->pdo->beginTransaction();

        try {
            // 1. Update Table COMMANDE
            $stmt = $this->pdo->prepare("UPDATE COMMANDE SET statut = :statut WHERE id_commande = :id");
            $stmt->execute(['statut' => $newStatus, 'id' => $id]);

            // 2. Insert History
            if ($newStatus === 'ANNULEE' && $modeContact) {
                // Cas spécifique Annulation avec mode de contact
                $stmtHist = $this->pdo->prepare("INSERT INTO COMMANDE_ANNULATION (id_commande, annule_par, mode_contact, motif) VALUES (:id, :by, :mode, :motif)");
                $stmtHist->execute([
                    'id' => $id,
                    'by' => $modifiedBy,
                    'mode' => $modeContact,
                    'motif' => $motif ?? 'Pas de motif spécifié'
                ]);
            } else {
                // Cas standard
                $this->addHistory($id, $newStatus, $modifiedBy, $motif);
            }

            $this->pdo->commit();
            return true;

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            // On pourrait logger l'erreur ici
            throw $e;
        }
    }

    /**
     * Met à jour les informations d'une commande (modification client).
     * @deprecated Use updateObject instead if possible, keeping for backward compat or redefining.
     * Renamed to avoid collision with the new update(Commande $obj) method.
     */
    public function updateLegacy(int $id, array $data, int $modifiedBy): bool
    {
        // On ne permet la modification que de certains champs via cette méthode
        // Mapping fields vers colonnes
        $allowedFields = [
            'adresseLivraison' => 'adresse_livraison',
            'ville' => 'ville',
            'codePostal' => 'code_postal',
            'datePrestation' => 'date_prestation',
            'heureLivraison' => 'heure_livraison',
            'gsm' => 'gsm',
            'nombrePersonnes' => 'nombre_personnes',
            'prixTotal' => 'prix_total',
            'fraisLivraison' => 'frais_livraison',
            'montantReduction' => 'montant_reduction',
            // On ajoute les recalculs
        ];

        $updates = [];
        $params = [];
        $recordedChanges = [];

        foreach ($data as $key => $value) {
            if (isset($allowedFields[$key])) {
                $col = $allowedFields[$key];
                $updates[] = "$col = :$key";
                $params[$key] = $value;
                $recordedChanges[$key] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $params['id'] = $id;

        $this->pdo->beginTransaction();
        try {
            $sql = "UPDATE COMMANDE SET " . implode(', ', $updates) . " WHERE id_commande = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            // Log modification
            $stmtLog = $this->pdo->prepare("INSERT INTO COMMANDE_MODIFICATION (id_commande, modifie_par, champs_modified) VALUES (:id, :by, :json)");
            $stmtLog->execute([
                'id' => $id,
                'by' => $modifiedBy,
                'json' => json_encode($recordedChanges)
            ]);

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Gère le prêt de matériel pour une commande.
     */
    public function setMateriel(int $commandeId, array $materiels): void
    {
        // $materiels attendu : [['id' => int, 'quantite' => int]]
        $this->pdo->beginTransaction();
        try {
            // Récupérer la date de prestation pour calculer date_retour_prevu (RG9 : prestation + 10 jours)
            $stmtDate = $this->pdo->prepare("SELECT date_prestation FROM COMMANDE WHERE id_commande = :id");
            $stmtDate->execute(['id' => $commandeId]);
            $datePrestation = $stmtDate->fetchColumn();

            foreach ($materiels as $mat) {
                // 1. Insert COMMANDE_MATERIEL
                // date_retour_prevu = date_prestation + 10 jours (RG9 : délai de restitution)
                // Fallback sur NOW()+10j si date_prestation absente (cas exceptionnel)
                if ($datePrestation) {
                    $sql = "INSERT INTO COMMANDE_MATERIEL (id_commande, id_materiel, quantite, date_pret, date_retour_prevu) 
                            VALUES (:cmdId, :matId, :qty, NOW(), DATE_ADD(:datePrestation, INTERVAL 10 DAY))";
                } else {
                    $sql = "INSERT INTO COMMANDE_MATERIEL (id_commande, id_materiel, quantite, date_pret, date_retour_prevu) 
                            VALUES (:cmdId, :matId, :qty, NOW(), DATE_ADD(NOW(), INTERVAL 10 DAY))";
                }
                
                $stmt = $this->pdo->prepare($sql);
                $params = [
                    'cmdId' => $commandeId,
                    'matId' => $mat['id'],
                    'qty' => $mat['quantite']
                ];
                if ($datePrestation) {
                    $params['datePrestation'] = $datePrestation;
                }
                $stmt->execute($params);

                // 2. Decrement stock MATERIEL
                $upd = $this->pdo->prepare("UPDATE MATERIEL SET stock_disponible = stock_disponible - :qty WHERE id_materiel = :id");
                $upd->execute(['qty' => $mat['quantite'], 'id' => $mat['id']]);
            }
            
            // Update commande flag
            $stmt = $this->pdo->prepare("UPDATE COMMANDE SET materiel_pret = 1 WHERE id_commande = :id");
            $stmt->execute(['id' => $commandeId]);

            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Enregistre le retour du matériel pour une commande.
     * Cette méthode remet le stock et met à jour la date de retour effectif.
     */
    public function returnMateriel(int $commandeId): void
    {
        $this->pdo->beginTransaction();
        try {
            // 1. Récupérer tout le matériel prêté pour cette commande qui n'est pas encore rendu
            $stmt = $this->pdo->prepare("SELECT id_materiel, quantite FROM COMMANDE_MATERIEL WHERE id_commande = :id AND date_retour_effectif IS NULL");
            $stmt->execute(['id' => $commandeId]);
            $materiels = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($materiels)) {
                // Rien à rendre ou déjà rendu
                $this->pdo->commit();
                return;
            }

            foreach ($materiels as $mat) {
                // 2. Mettre à jour la date de retour effectif + flag retourne
                $updLink = $this->pdo->prepare("UPDATE COMMANDE_MATERIEL SET date_retour_effectif = NOW(), retourne = TRUE WHERE id_commande = :cmdId AND id_materiel = :matId");
                $updLink->execute(['cmdId' => $commandeId, 'matId' => $mat['id_materiel']]);

                // 3. Ré-incrémenter le stock MATERIEL
                $updStock = $this->pdo->prepare("UPDATE MATERIEL SET stock_disponible = stock_disponible + :qty WHERE id_materiel = :matId");
                $updStock->execute(['qty' => $mat['quantite'], 'matId' => $mat['id_materiel']]);
            }

            // 4. Mettre à jour le flag de la commande (optionnel, mais utile pour savoir si tout est rendu)
            // On vérifie s'il reste du matériel non rendu
            $check = $this->pdo->prepare("SELECT COUNT(*) FROM COMMANDE_MATERIEL WHERE id_commande = :id AND date_retour_effectif IS NULL");
            $check->execute(['id' => $commandeId]);
            $remaining = $check->fetchColumn();

            if ($remaining == 0) {
                // Tout est rendu → écrire la date de retour sur la commande
                $updCmd = $this->pdo->prepare("UPDATE COMMANDE SET date_retour_materiel = NOW() WHERE id_commande = :id");
                $updCmd->execute(['id' => $commandeId]);
            }

            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Récupère l'historique complet des statuts d'une commande.
     * @return array Liste chronologique des changements
     */
    public function getTimeline(int $commandeId): array
    {
        $sql = "SELECT s.*, u.nom, u.prenom, u.role 
                FROM COMMANDE_STATUT s
                LEFT JOIN UTILISATEUR u ON s.modifie_par = u.id_utilisateur
                WHERE s.id_commande = :id 
                ORDER BY s.date_changement ASC";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $commandeId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère la liste du matériel associé à une commande.
     * @return array Liste des matériels prêtés
     */
    public function getMateriels(int $commandeId): array
    {
        $sql = "SELECT cm.id_materiel, m.libelle, cm.quantite, cm.date_pret,
                       cm.date_retour_prevu, cm.date_retour_effectif,
                       (cm.date_retour_effectif IS NOT NULL) AS retourne
                FROM COMMANDE_MATERIEL cm
                JOIN MATERIEL m ON cm.id_materiel = m.id_materiel
                WHERE cm.id_commande = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $commandeId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Helper pour ajouter une ligne dans COMMANDE_STATUT
     */
    private function addHistory(int $commandeId, string $status, int $modifiedBy, ?string $comment = null): void
    {
        $sql = "INSERT INTO COMMANDE_STATUT (id_commande, statut, modifie_par, commentaire) VALUES (:id, :statut, :by, :comment)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $commandeId,
            'statut' => $status,
            'by' => $modifiedBy,
            'comment' => $comment
        ]);
    }

    /**
     * Mapper DB row -> Model Array keys
     */
    private function mapToModel(array $row): array
    {
        return [
            'id' => $row['id_commande'],
            'userId' => $row['id_utilisateur'],
            'menuId' => $row['id_menu'],
            'dateCommande' => $row['date_commande'],
            'datePrestation' => $row['date_prestation'],
            'heureLivraison' => $row['heure_livraison'],
            'adresseLivraison' => $row['adresse_livraison'],
            'ville' => $row['ville'],
            'codePostal' => $row['code_postal'],
            'gsm' => $row['gsm'],
            'nombrePersonnes' => $row['nombre_personnes'],
            'nombrePersonneMinSnapshot' => $row['nombre_personne_min_snapshot'],
            'prixMenuUnitaire' => $row['prix_menu_unitaire'],
            'montantReduction' => $row['montant_reduction'],
            'reductionAppliquee' => (bool)$row['reduction_appliquee'],
            'fraisLivraison' => $row['frais_livraison'],
            'prixTotal' => $row['prix_total'],
            'horsBordeaux' => (bool)$row['hors_bordeaux'],
            'distanceKm' => $row['distance_km'],
            'statut' => $row['statut'],
            'hasAvis' => (bool)$row['has_avis'],
            'materielPret' => (bool)$row['materiel_pret'],
            'dateLivraisonEffective' => $row['date_livraison_effective'],
            'dateRetourMateriel' => $row['date_retour_materiel'],
        ];
    }

    /**
     * Récupère les commandes avec du matériel en retard (date_retour_prevu dépassée, non rendu).
     * @return array Liste des commandes en retard avec détails matériel et client
     */
    public function findOverdueMaterials(): array
    {
        $sql = "SELECT 
                    c.id_commande,
                    c.id_utilisateur,
                    c.date_prestation,
                    c.statut,
                    u.prenom,
                    u.nom,
                    u.email,
                    u.gsm,
                    m.libelle AS materiel_libelle,
                    cm.quantite,
                    cm.date_pret,
                    cm.date_retour_prevu,
                    DATEDIFF(NOW(), cm.date_retour_prevu) AS jours_retard
                FROM COMMANDE_MATERIEL cm
                JOIN COMMANDE c ON cm.id_commande = c.id_commande
                JOIN UTILISATEUR u ON c.id_utilisateur = u.id_utilisateur
                JOIN MATERIEL m ON cm.id_materiel = m.id_materiel
                WHERE cm.date_retour_effectif IS NULL
                  AND cm.date_retour_prevu < NOW()
                ORDER BY cm.date_retour_prevu ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recherche avancée pour le dashboard employé.
     * @param array $filters ['status' => 'EN_ATTENTE', 'userId' => 12, 'date' => '2023-10-10']
     */
    public function findByFilters(array $filters): array
    {
        // Note: La table COMMANDE a déjà une colonne 'statut' (dénormalisée / snapshot)
        // On peut l'utiliser directement pour la performance plutôt que de joindre COMMANDE_STATUT
        $sql = "SELECT * FROM COMMANDE WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND statut = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['userId'])) {
            $sql .= " AND id_utilisateur = :userId";
            $params[':userId'] = $filters['userId'];
        }

        if (!empty($filters['date'])) {
            $sql .= " AND DATE(date_prestation) = :date";
            $params[':date'] = $filters['date'];
        }

        $sql .= " ORDER BY date_prestation DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $commandes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $commandes[] = $this->mapToModel($row);
        }
        return $commandes;
    }
}
