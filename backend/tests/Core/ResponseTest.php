<?php

namespace Tests\Core;

use App\Core\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function test_constructor_sets_default_values(): void
    {
        $response = new Response();
        $this->assertSame(null, $this->getPrivateProperty($response, 'content'));
        $this->assertSame(200, $this->getPrivateProperty($response, 'statusCode'));
        $this->assertSame([], $this->getPrivateProperty($response, 'headers'));
    }

    public function test_constructor_sets_provided_values(): void
    {
        $response = new Response('Hello', 404, ['X-Test' => 'true']);
        $this->assertSame('Hello', $this->getPrivateProperty($response, 'content'));
        $this->assertSame(404, $this->getPrivateProperty($response, 'statusCode'));
        $this->assertSame(['X-Test' => 'true'], $this->getPrivateProperty($response, 'headers'));
    }

    public function test_setContent(): void
    {
        $response = new Response();
        $response->setContent('New Content');
        $this->assertSame('New Content', $this->getPrivateProperty($response, 'content'));
    }

    public function test_setJsonContent(): void
    {
        $response = new Response();
        $data = ['success' => true, 'id' => 42];
        $response->setJsonContent($data);

        $expectedJson = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->assertSame($expectedJson, $this->getPrivateProperty($response, 'content'));
        
        $headers = $this->getPrivateProperty($response, 'headers');
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertSame('application/json; charset=utf-8', $headers['Content-Type']);
    }

    public function test_setStatusCode(): void
    {
        $response = new Response();
        $response->setStatusCode(Response::HTTP_CREATED);
        $this->assertSame(201, $this->getPrivateProperty($response, 'statusCode'));
    }

    public function test_setHeader(): void
    {
        $response = new Response();
        $response->setHeader('Cache-Control', 'no-cache');
        $headers = $this->getPrivateProperty($response, 'headers');
        $this->assertSame(['Cache-Control' => 'no-cache'], $headers);

        // Test overwriting a header
        $response->setHeader('Cache-Control', 'public, max-age=3600');
        $headers = $this->getPrivateProperty($response, 'headers');
        $this->assertSame(['Cache-Control' => 'public, max-age=3600'], $headers);
    }

    /**
     * Helper pour accéder aux propriétés privées pour les tests.
     */
    private function getPrivateProperty(object $object, string $property)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}
