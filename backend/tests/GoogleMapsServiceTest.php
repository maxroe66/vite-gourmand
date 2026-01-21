<?php

use PHPUnit\Framework\TestCase;
use App\Services\GoogleMapsService;

// Classe dérivée pour surcharger la méthode protégée qui fait l'appel HTTP
class TestableGoogleMapsService extends GoogleMapsService
{
    public string $mockResponse = '';
    public string $lastUrl = '';

    protected function makeHttpRequest(string $url): string
    {
        $this->lastUrl = $url;
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
        
        // Mock réponse Google Maps Success
        $service->mockResponse = json_encode([
            'status' => 'OK',
            'rows' => [
                [
                    'elements' => [
                        [
                            'status' => 'OK',
                            'distance' => [
                                'value' => 50000 // 50 km
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $distance = $service->getDistance('Libourne, France');
        
        $this->assertEquals(50.0, $distance);
        $this->assertStringContainsString('key=valid_key', $service->lastUrl);
    }

    public function testGetDistanceApiErrorFallback(): void
    {
        $service = new TestableGoogleMapsService('valid_key');
        
        // Mock réponse invalide/erreur
        $service->mockResponse = json_encode(['status' => 'REQUEST_DENIED']);

        // Le fallback pour une adresse sans "33" est de 50.0
        // Pour être sûr de tester le fallback, prenons une adresse inconnue hors gironde
        $distance = $service->getDistance('Paris');
        $this->assertEquals(50.0, $distance);

        // Fallback adresse gironde (33) -> 15.0
        $service->mockResponse = json_encode(['status' => 'REQUEST_DENIED']);
        $distance33 = $service->getDistance('Langon 33210');
        $this->assertEquals(15.0, $distance33);
    }
}
