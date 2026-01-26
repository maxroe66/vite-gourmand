<?php

use PHPUnit\Framework\TestCase;
use App\Services\GoogleMapsService;

// Classe dérivée pour surcharger la méthode protégée qui fait l'appel HTTP
class TestableGoogleMapsService extends GoogleMapsService
{
    public string $mockResponse = '';
    public string $lastUrl = '';
    public array $lastOptions = [];

    protected function makeHttpRequest(string $url, array $options = []): string
    {
        $this->lastUrl = $url;
        $this->lastOptions = $options;
        return $this->mockResponse;
    }
}

class GoogleMapsServiceTest extends TestCase
{
    public function testGetDistanceReturnsZeroForBordeaux(): void
    {
        $service = new TestableGoogleMapsService('fake_key');
        // Test 33000
        $this->assertEquals(0.0, $service->getDistance('10 Rue Ste Catherine, 33000 Bordeaux'));
        // Test "Bordeaux" in string
        $this->assertEquals(0.0, $service->getDistance('Mairie de Bordeaux'));
    }

    public function testGetDistanceApiSuccess(): void
    {
        $service = new TestableGoogleMapsService('valid_key');
        
        // Mock réponse Routes API (v2) Success
        $service->mockResponse = json_encode([
            [
                'originIndex' => 0,
                'destinationIndex' => 0,
                'status' => [], // Empty status = OK
                'distanceMeters' => 50000, // 50 km
                'condition' => 'ROUTE_EXISTS'
            ]
        ]);

        $distance = $service->getDistance('Libourne, France');
        
        $this->assertEquals(50.0, $distance);
        // Verify URL is for Routes API
        $this->assertStringContainsString('routes.googleapis.com', $service->lastUrl);
        // Verify method POST
        $this->assertEquals('POST', $service->lastOptions['http']['method']);
    }

    public function testGetDistanceApiErrorFallback(): void
    {
        $service = new TestableGoogleMapsService('valid_key');
        
        // Mock réponse invalide/erreur Routes API
        $service->mockResponse = json_encode(['error' => ['code' => 403, 'message' => 'PERMISSION_DENIED']]);

        // Le fallback pour une adresse sans "33" est de 50.0
        $distance = $service->getDistance('Paris');
        $this->assertEquals(50.0, $distance);

        // Fallback adresse gironde (33) -> 15.0
        $service->mockResponse = json_encode(['error' => ['code' => 403, 'message' => 'PERMISSION_DENIED']]);
        $distance33 = $service->getDistance('Langon 33210');
        $this->assertEquals(15.0, $distance33);
    }
}
