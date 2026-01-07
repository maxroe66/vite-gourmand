<?php

namespace App\Middlewares;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    private static $decodedToken = null;

    public static function check(array $config)
    {
        // Vérifier le cookie authToken en priorité
        if (!isset($_COOKIE['authToken'])) {
            // Fallback: vérifier le header Authorization (pour compatibilité API)
            $headers = getallheaders();
            if (!isset($headers['Authorization'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Token manquant']);
                exit;
            }
            $token = str_replace('Bearer ', '', $headers['Authorization']);
        } else {
            $token = $_COOKIE['authToken'];
        }

        $secret = $config['jwt']['secret'];

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            self::$decodedToken = $decoded; // Stocker le token décodé
            return $decoded;
        } catch (\Exception $e) {
            http_response_code(401);
            echo json_encode(['error' => 'Token invalide', 'details' => $e->getMessage()]);
            exit;
        }
    }

    public static function getDecodedToken()
    {
        return self::$decodedToken;
    }
}
