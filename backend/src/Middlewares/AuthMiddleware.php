<?php
namespace App\Middlewares;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    public static function check()
    {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Token manquant']);
            exit;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        $secret = $_ENV['JWT_SECRET']; // ou depuis la config

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
