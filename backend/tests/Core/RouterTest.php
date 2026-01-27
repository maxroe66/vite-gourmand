<?php

namespace Tests\Core;

use App\Core\Router;
use App\Core\Response;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class RouterTest extends TestCase
{
    public function testReturns405WithAllowHeaderWhenMethodNotAllowed(): void
    {
        $router = new Router();
        $router->get('/foo', fn ($c, $p, $r) => new Response('ok'));
        $router->post('/foo', fn ($c, $p, $r) => new Response('ok'));

        $container = new class implements ContainerInterface {
            public function get(string $id)
            {
                return null;
            }
            public function has(string $id): bool
            {
                return false;
            }
        };

        $response = $router->dispatch('DELETE', '/foo', $container);

        $this->assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
        $this->assertSame('GET, POST', $response->getHeaders()['Allow'] ?? null);
    }

    public function testReturns404WhenNoRouteMatches(): void
    {
        $router = new Router();
        $router->get('/foo', fn ($c, $p, $r) => new Response('ok'));

        $container = new class implements ContainerInterface {
            public function get(string $id)
            {
                return null;
            }
            public function has(string $id): bool
            {
                return false;
            }
        };

        $response = $router->dispatch('GET', '/bar', $container);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
