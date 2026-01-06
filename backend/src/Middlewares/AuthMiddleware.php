<?php
namespace App\Middlewares;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    public static function check()
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

        $config = require __DIR__ . '/../../config/config.php';
        $secret = $config['jwt']['secret'];

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            // stocker l'utilisateur dans une variable globale ou session si besoin
            return $decoded;
        } catch (\Exception $e) {
            http_response_code(401);
            echo json_encode(['error' => 'Token invalide', 'details' => $e->getMessage()]);
            exit;
        }
    }
}
