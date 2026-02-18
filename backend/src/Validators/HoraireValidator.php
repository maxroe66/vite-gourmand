<?php

namespace App\Validators;

class HoraireValidator
{
    /**
     * Valide les données de mise à jour d'un horaire.
     * @param array $data
     * @return array [isValid => bool, errors => array]
     */
    public function validate(array $data): array
    {
        $errors = [];
        $ferme = isset($data['ferme']) ? (bool)$data['ferme'] : false;

        // Si le jour est marqué fermé, les heures sont facultatives
        if (!$ferme) {
            // Heure d'ouverture (requise si pas fermé)
            if (empty($data['heureOuverture']) && empty($data['heure_ouverture'])) {
                $errors['heureOuverture'] = "L'heure d'ouverture est requise si le jour n'est pas fermé.";
            } else {
                $heure = $data['heureOuverture'] ?? $data['heure_ouverture'] ?? '';
                if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $heure)) {
                    $errors['heureOuverture'] = "Le format de l'heure d'ouverture est invalide (HH:MM attendu).";
                }
            }

            // Heure de fermeture (requise si pas fermé)
            if (empty($data['heureFermeture']) && empty($data['heure_fermeture'])) {
                $errors['heureFermeture'] = "L'heure de fermeture est requise si le jour n'est pas fermé.";
            } else {
                $heure = $data['heureFermeture'] ?? $data['heure_fermeture'] ?? '';
                if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $heure)) {
                    $errors['heureFermeture'] = "Le format de l'heure de fermeture est invalide (HH:MM attendu).";
                }
            }

            // Vérifier que fermeture > ouverture
            $ouverture = $data['heureOuverture'] ?? $data['heure_ouverture'] ?? '';
            $fermeture = $data['heureFermeture'] ?? $data['heure_fermeture'] ?? '';
            if ($ouverture && $fermeture && $fermeture <= $ouverture) {
                $errors['heureFermeture'] = "L'heure de fermeture doit être postérieure à l'heure d'ouverture.";
            }
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }
}
