<?php
namespace App\Core;

final class Router {
    private array $routes = ['GET'=>[], 'POST'=>[], 'PUT'=>[], 'PATCH'=>[], 'DELETE'=>[]];
    public function __construct(private array $config) {}

    public function get(string $path, callable $h): void    { $this->routes['GET'][$path]    = $h; }
    public function post(string $path, callable $h): void   { $this->routes['POST'][$path]   = $h; }
    public function put(string $path, callable $h): void    { $this->routes['PUT'][$path]    = $h; }
    public function patch(string $path, callable $h): void  { $this->routes['PATCH'][$path]  = $h; }
    public function delete(string $path, callable $h): void { $this->routes['DELETE'][$path] = $h; }

    public function dispatch(string $method, string $path): void {
        $handler = $this->routes[$method][$path] ?? null;
        if (!$handler) { Response::json(['error' => 'Not Found'], 404); }
        $result = $handler($this->config);
        if (is_array($result)) { Response::json($result); }
    }
}
