<?php

namespace App\Validators;

class CommandeValidator
{
    /**
     * Valide les données de création de commande.
     * @param array $data Données du body request
     * @return array Tableau des erreurs (vide si OK)
     */
    public function validateCreate(array $data): array
    {
        $errors = [];

        if (empty($data['menuId']) || !filter_var($data['menuId'], FILTER_VALIDATE_INT)) {
            $errors['menuId'] = 'Le choix du menu est invalide.';
        }

        if (empty($data['nombrePersonnes']) || !filter_var($data['nombrePersonnes'], FILTER_VALIDATE_INT) || $data['nombrePersonnes'] <= 0) {
            $errors['nombrePersonnes'] = 'Le nombre de personnes doit être un entier positif.';
        }

        if (empty($data['adresseLivraison'])) {
            $errors['adresseLivraison'] = "L'adresse de livraison est requise.";
        }

        if (empty($data['datePrestation'])) {
            $errors['datePrestation'] = "La date de prestation est requise.";
        } else {
            // Validation format YYYY-MM-DD
            $d = \DateTime::createFromFormat('Y-m-d', $data['datePrestation']);
            if (!$d || $d->format('Y-m-d') !== $data['datePrestation']) {
                $errors['datePrestation'] = "Format de date invalide (YYYY-MM-DD attendu).";
            } else {
                // Règle métier : Date > Now + 24h
                $prestationDate = new \DateTime($data['datePrestation']);
                $minDate = (new \DateTime())->modify('+1 day');
                if ($prestationDate < $minDate) {
                    $errors['datePrestation'] = "La prestation doit être commandée au moins 24h à l'avance.";
                }
            }
        }

        if (empty($data['heureLivraison'])) {
             $errors['heureLivraison'] = "L'heure de livraison est requise.";
        }

        // GSM : Requis + Format simple check
        if (empty($data['gsm'])) {
             $errors['gsm'] = "Le numéro GSM est requis.";
        } elseif (!preg_match('/^[0-9+ ]{10,15}$/', $data['gsm'])) {
             $errors['gsm'] = "Le numéro GSM est invalide.";
        }

        return $errors;
    }
}
