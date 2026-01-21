<?php

namespace App\Services;

class GoogleMapsService
{
    private string $apiKey;
    private const BORDEAUX_COORDS = '44.837789,-0.57918'; // Not used if text search, but useful.

    public function __construct()
    {
        $this->apiKey = $_ENV['GOOGLE_MAPS_API_KEY'] ?? '';
    }

    /**
     * Calcule la distance en KM entre une adresse et Bordeaux.
     * Utilise l'API Google Maps Distance Matrix.
     * Fallback sur une estimation si l'API échoue.
     */
    public function getDistance(string $originAddress, string $destination = 'Bordeaux, France'): float
    {
        // 1. Optimisation locale : Si Code Postal Bordeaux
        if ($this->isBordeaux($originAddress)) {
            return 0.0;
        }

        if (empty($this->apiKey)) {
            // Pas de clé API configurée => Fallback direct
            return $this->estimateDistance($originAddress);
        }

        try {
            $url = "https://maps.googleapis.com/maps/api/distancematrix/json?" . http_build_query([
                'origins' => $originAddress,
                'destinations' => $destination,
                'key' => $this->apiKey,
                'units' => 'metric'
            ]);

            // Utilisation de file_get_contents pour faire simple (ou cURL si dispo)
            // On va utiliser une méthode protégée pour mocker facilement en test
            $response = $this->makeHttpRequest($url);
            $data = json_decode($response, true);

            if (isset($data['status']) && $data['status'] === 'OK') {
                $row = $data['rows'][0]['elements'][0];
                if ($row['status'] === 'OK') {
                    // Distance en mètres -> converti en km
                    return round($row['distance']['value'] / 1000, 2);
                }
            }
            
            // Si l'API répond mais ne trouve pas de route ou erreur
            return $this->estimateDistance($originAddress);

        } catch (\Exception $e) {
            // Log warning here
            return $this->estimateDistance($originAddress);
        }
    }

    private function isBordeaux(string $address): bool
    {
        // Vérification simple sur le code postal "33000" ou le mot "Bordeaux"
        return strpos($address, '33000') !== false || stripos($address, 'Bordeaux') !== false;
    }

    private function estimateDistance(string $address): float
    {
        // Logique de fallback estimative (Doc Tech)
        // Si c'est en Gironde (33), on estime une moyenne, sinon plus loin.
        if (strpos($address, '33') !== false) {
            return 15.0; // Moyenne CUB / Gironde proche
        }
        return 50.0; // Par défaut loin
    }

    protected function makeHttpRequest(string $url): string
    {
        // Wrapper pour testabilité
        $opts = [
            "http" => [
                "timeout" => 5 // 5 seconds timeout
            ]
        ];
        $context = stream_context_create($opts);
        $result = @file_get_contents($url, false, $context);
        
        if ($result === false) {
            throw new \Exception("Request failed");
        }
        return $result;
    }
}
