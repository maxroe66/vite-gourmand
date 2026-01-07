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
        foreach ($this->routes[$method] as $routePath => $handler) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_]+)', $routePath);
            if (preg_match("#^$pattern$#", $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                // Le conteneur et les paramètres sont maintenant passés en argument au handler
                $handler($container, $params);
                return;
            }
        }

        Response::json(['success' => false, 'message' => 'Route non trouvée'], 404);
    }
}
