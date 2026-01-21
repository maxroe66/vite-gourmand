<?php

namespace App\Models;

class Avis
{
    public ?int $id = null;
    public int $note;
    public string $commentaire;
    public string $statutValidation = 'EN_ATTENTE'; // VALIDE, REFUSE, EN_ATTENTE
    public string $dateAvis;
    public int $userId;
    public int $commandeId;
    public int $menuId;
    public ?int $moderePar = null;
    public ?string $dateValidation = null;

    // Helper pour convertir depuis DB
    public static function fromArray(array $data): self
    {
        $avis = new self();
        $avis->id = $data['id_avis_fallback'] ?? null;
        $avis->note = (int)$data['note'];
        $avis->commentaire = $data['commentaire'];
        $avis->statutValidation = $data['statut_validation'];
        $avis->dateAvis = $data['date_avis'];
        $avis->userId = (int)$data['id_utilisateur'];
        $avis->commandeId = (int)$data['id_commande'];
        $avis->menuId = (int)$data['id_menu'];
        $avis->moderePar = isset($data['modere_par']) ? (int)$data['modere_par'] : null;
        $avis->dateValidation = $data['date_validation'] ?? null;

        return $avis;
    }
}
