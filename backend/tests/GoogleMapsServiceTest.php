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
        
        // Mock réponse Distance Matrix API Success
        $service->mockResponse = json_encode([
            'status' => 'OK',
            'rows' => [
                [
                    'elements' => [
                        [
                            'status' => 'OK',
                            'distance' => [
                                'text' => '50 km',
                                'value' => 50000 // 50 km en mètres
                            ],
                            'duration' => [
                                'text' => '45 mins',
                                'value' => 2700
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $distance = $service->getDistance('Libourne, France');
        
        $this->assertEquals(50.0, $distance);
        // Verify URL is for Distance Matrix API
        $this->assertStringContainsString('maps.googleapis.com/maps/api/distancematrix', $service->lastUrl);
        // Verify method GET
        $this->assertEquals('GET', $service->lastOptions['http']['method']);
    }

    public function testGetDistanceApiErrorFallback(): void
    {
        $service = new TestableGoogleMapsService('valid_key');
        
        // Mock réponse erreur Distance Matrix API
        $service->mockResponse = json_encode([
            'status' => 'REQUEST_DENIED',
            'error_message' => 'The provided API key is invalid.'
        ]);

        // Le fallback pour une adresse sans "33" est de 50.0
        $distance = $service->getDistance('Paris');
        $this->assertEquals(50.0, $distance);

        // Fallback adresse gironde (33) -> 15.0
        $service->mockResponse = json_encode([
            'status' => 'REQUEST_DENIED',
            'error_message' => 'The provided API key is invalid.'
        ]);
        $distance33 = $service->getDistance('Langon 33210');
        $this->assertEquals(15.0, $distance33);
    }
}
