<?php

use PHPUnit\Framework\TestCase;
use App\Services\AuthService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    
    protected function setUp(): void
    {
        $config = require __DIR__ . '/../config/config.php';
        $this->authService = new AuthService($config);
    }

    public function testGenerateToken(): void
    {
        // Debug : afficher le secret chargé
        $config = require __DIR__ . '/../config/config.php';
        $secret = $config['jwt']['secret'];
        echo "\n🔍 JWT_SECRET chargé: " . $secret . " (longueur: " . strlen($secret) . " bytes)\n";
        
        // Générer un token
        $token = $this->authService->generateToken(123, 'client');
        
        // Vérifier que le token n'est pas vide
        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        
        echo "🔑 Token généré: " . substr($token, 0, 50) . "...\n";
    }
    
    public function testTokenCanBeDecoded(): void
    {
        // Générer un token
        $userId = 456;
        $role = 'admin';
        $token = $this->authService->generateToken($userId, $role);
        
        // Charger la config pour obtenir le secret
        $config = require __DIR__ . '/../config/config.php';
        $secret = $config['jwt']['secret'];
        
        // Décoder le token
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        
        // Vérifier le contenu
        $this->assertEquals($userId, $decoded->sub);
        $this->assertEquals($role, $decoded->role);
        $this->assertEquals('vite-gourmand', $decoded->iss);
        $this->assertGreaterThan(time() - 5, $decoded->iat); // émis il y a moins de 5 secondes
        $this->assertGreaterThan(time(), $decoded->exp); // pas encore expiré
        
        echo "\n✅ Token décodé avec succès:\n";
        echo "   - User ID: {$decoded->sub}\n";
        echo "   - Role: {$decoded->role}\n";
        echo "   - Émis à: " . date('Y-m-d H:i:s', $decoded->iat) . "\n";
        echo "   - Expire à: " . date('Y-m-d H:i:s', $decoded->exp) . "\n";
    }
    
    public function testHashPassword(): void
    {
        $password = 'MonMotDePasse123!';
        $hash = $this->authService->hashPassword($password);
        
        $this->assertNotEmpty($hash);
        $this->assertStringStartsWith('$2y$', $hash); // bcrypt
        $this->assertNotEquals($password, $hash);
        
        echo "\n🔒 Password hashé: " . substr($hash, 0, 30) . "...\n";
    }
}
