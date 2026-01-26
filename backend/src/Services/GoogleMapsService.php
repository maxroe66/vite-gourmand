<?php

namespace App\Services;

class GoogleMapsService
{
    private string $apiKey;
    private const BORDEAUX_COORDS = '44.837789,-0.57918'; // Not used if text search, but useful.

    public function __construct(string $apiKey = '')
    {
        $this->apiKey = $apiKey ?: ($_ENV['GOOGLE_MAPS_API_KEY'] ?? '');
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
            // Utilisation de l'API Distance Matrix (legacy mais plus couramment activée)
            // https://developers.google.com/maps/documentation/distance-matrix/overview
            $originEncoded = urlencode($originAddress);
            $destinationEncoded = urlencode($destination);
            
            $url = "https://maps.googleapis.com/maps/api/distancematrix/json" .
                   "?origins=" . $originEncoded .
                   "&destinations=" . $destinationEncoded .
                   "&key=" . $this->apiKey .
                   "&language=fr" .
                   "&mode=driving";

            $opts = [
                "http" => [
                    "method" => "GET",
                    "timeout" => 5,
                    "ignore_errors" => true
                ]
            ];

            $response = $this->makeHttpRequest($url, $opts);
            $data = json_decode($response, true);

            // Log pour debug
            error_log("Google Maps API Response: " . $response);

            // Vérification du statut global
            if (!isset($data['status'])) {
                error_log("Google Maps API: No status in response");
                return $this->estimateDistance($originAddress);
            }

            if ($data['status'] !== 'OK') {
                error_log("Google Maps API Error Status: " . $data['status'] . " - " . ($data['error_message'] ?? 'No message'));
                
                // Si l'API legacy n'est pas activée, essayer l'API Routes v2
                if ($data['status'] === 'REQUEST_DENIED') {
                    return $this->tryRoutesApi($originAddress, $destination);
                }
                
                return $this->estimateDistance($originAddress);
            }

            // Extraction de la distance
            if (isset($data['rows'][0]['elements'][0])) {
                $element = $data['rows'][0]['elements'][0];
                
                if ($element['status'] === 'OK' && isset($element['distance']['value'])) {
                    // Distance en mètres -> converti en km
                    $distanceKm = round($element['distance']['value'] / 1000, 2);
                    error_log("Google Maps Distance calculated: " . $distanceKm . " km");
                    return $distanceKm;
                } else {
                    error_log("Google Maps Element Error: " . ($element['status'] ?? 'Unknown'));
                }
            }
            
            // Si l'API répond mais ne trouve pas de route
            return $this->estimateDistance($originAddress);

        } catch (\Exception $e) {
            error_log("Google Maps Exception: " . $e->getMessage());
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

    /**
     * Fallback sur l'API Routes v2 si l'API Distance Matrix legacy n'est pas activée
     */
    private function tryRoutesApi(string $originAddress, string $destination): float
    {
        try {
            $url = "https://routes.googleapis.com/distanceMatrix/v2:computeRouteMatrix";

            $payload = [
                'origins' => [
                    ['waypoint' => ['address' => $originAddress]]
                ],
                'destinations' => [
                    ['waypoint' => ['address' => $destination]]
                ],
                'travelMode' => 'DRIVE',
                'routingPreference' => 'TRAFFIC_AWARE'
            ];

            $opts = [
                "http" => [
                    "method" => "POST",
                    "header" => "Content-Type: application/json\r\n" .
                                "X-Goog-Api-Key: " . $this->apiKey . "\r\n" .
                                "X-Goog-FieldMask: originIndex,destinationIndex,duration,distanceMeters,status,condition\r\n",
                    "content" => json_encode($payload),
                    "timeout" => 5,
                    "ignore_errors" => true
                ]
            ];

            $response = $this->makeHttpRequest($url, $opts);
            $data = json_decode($response, true);

            error_log("Google Maps Routes API Response: " . $response);

            if (is_array($data) && !empty($data)) {
                $element = $data[0];

                // Vérification d'erreur dans la réponse
                if (isset($element['error'])) {
                    error_log("Google Maps Routes API Error: " . ($element['error']['message'] ?? 'Unknown'));
                    return $this->estimateDistance($originAddress);
                }

                if (isset($element['distanceMeters'])) {
                    $distanceKm = round($element['distanceMeters'] / 1000, 2);
                    error_log("Google Maps Routes Distance: " . $distanceKm . " km");
                    return $distanceKm;
                }
            }

            return $this->estimateDistance($originAddress);

        } catch (\Exception $e) {
            error_log("Google Maps Routes Exception: " . $e->getMessage());
            return $this->estimateDistance($originAddress);
        }
    }

    protected function makeHttpRequest(string $url, array $options = []): string
    {
        // Wrapper pour testabilité
        if (empty($options)) {
            $options = [
                "http" => [
                    "timeout" => 5
                ]
            ];
        }

        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        if ($result === false) {
            throw new \Exception("Request failed");
        }
        return $result;
    }
}
