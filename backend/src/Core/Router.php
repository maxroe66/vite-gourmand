<?php

namespace App\Core;

use Psr\Container\ContainerInterface;

class Router
{
    private array $routes = [];
    private string $currentGroupPrefix = '';

    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[$method][$this->currentGroupPrefix . $path] = $handler;
    }

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    public function addGroup(string $prefix, callable $callback): void
    {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix = $previousGroupPrefix . $prefix;
        $callback($this);
        $this->currentGroupPrefix = $previousGroupPrefix;
    }

    public function dispatch(string $method, string $path, ContainerInterface $container): void
    {
        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            Response::json(['success' => false, 'message' => 'Route non trouvée'], 404);
            return;
        }

        // Le conteneur est maintenant passé en argument au handler de la route
        $handler($container);
    }
}
