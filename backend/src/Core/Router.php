<?php

namespace App\Core;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\AuthException;
use Psr\Container\ContainerInterface;

class Router
{
    private array $routes = [];
    private string $currentGroupPrefix = '';
    private ?array $lastRouteKey = null; // Clé pour retrouver la dernière route ajoutée

    /**
     * Ajoute une route et la mémorise pour y attacher un middleware.
     */
    public function add(string $method, string $path, callable $handler): self
    {
        $routePath = $this->currentGroupPrefix . $path;
        $this->routes[$method][$routePath] = [
            'handler' => $handler,
            'middlewares' => []
        ];
        $this->lastRouteKey = ['method' => $method, 'path' => $routePath];
        return $this; // Retourne $this pour permettre le chaînage (ex: ->middleware())
    }

    /**
     * Attache une classe de middleware à la dernière route ajoutée.
     */
    public function middleware(string $middlewareClass): self
    {
        if ($this->lastRouteKey) {
            $this->routes[$this->lastRouteKey['method']][$this->lastRouteKey['path']]['middlewares'][] = $middlewareClass;
        }
        return $this;
    }

    public function get(string $path, callable $handler): self
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): self
    {
        return $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): self
    {
        return $this->add('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): self
    {
        return $this->add('DELETE', $path, $handler);
    }

    public function addGroup(string $prefix, callable $callback): void
    {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix = $previousGroupPrefix . $prefix;
        $callback($this);
        $this->currentGroupPrefix = $previousGroupPrefix;
    }

    /**
     * Traite la requête : exécute les middlewares puis le contrôleur.
     */
    public function dispatch(string $method, string $path, ContainerInterface $container): void
    {
        // Création de l'objet Request qui sera passé à travers les couches
        $request = new Request();

        foreach ($this->routes[$method] as $routePath => $route) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_]+)', $routePath);
            if (preg_match("#^$pattern$#", $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Exécution des middlewares
                try {
                    foreach ($route['middlewares'] as $middlewareClass) {
                        $middleware = $container->get($middlewareClass);
                        // On passe l'objet Request au middleware pour qu'il puisse l'enrichir
                        $middleware->handle($request);
                    }
                } catch (AuthException $e) {
                    // Exception d'authentification : 401 Unauthorized
                    Response::json(['success' => false, 'message' => $e->getMessage()], 401);
                    return;
                } catch (\Exception $e) {
                    // Autres exceptions : 500 Internal Server Error
                    Response::json(['success' => false, 'message' => 'Erreur serveur interne'], 500);
                    return;
                }

                // Si tous les middlewares sont passés, on exécute le handler de la route
                // On passe également l'objet Request au contrôleur
                $route['handler']($container, $params, $request);
                return;
            }
        }

        Response::json(['success' => false, 'message' => 'Route non trouvée'], 404);
    }
}
