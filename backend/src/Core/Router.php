<?php

namespace App\Core;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\AuthException;
use App\Exceptions\ForbiddenException;
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
     * @param string $middlewareClass
     * @param array $args Arguments optionnels à passer au middleware
     */
    public function middleware(string $middlewareClass, array $args = []): self
    {
        if ($this->lastRouteKey) {
            $this->routes[$this->lastRouteKey['method']][$this->lastRouteKey['path']]['middlewares'][] = [
                'class' => $middlewareClass,
                'args' => $args
            ];
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

    public function patch(string $path, callable $handler): self
    {
        return $this->add('PATCH', $path, $handler);
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
     * Traite la requête, exécute les middlewares, appelle le contrôleur et retourne un objet Response.
     */
    public function dispatch(string $method, string $path, ContainerInterface $container): Response
    {
        // Création de l'objet Request qui sera passé à travers les couches
        $request = new Request();

        if (!isset($this->routes[$method])) {
             // Méthode HTTP non supportée ou aucune route définie pour cette méthode
             return (new Response())->setStatusCode(404)->setJsonContent(['error' => 'Route not found']);
        }

        foreach ($this->routes[$method] as $routePath => $route) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_]+)', $routePath);
            if (preg_match("#^$pattern$#", $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Exécution des middlewares
                try {
                    foreach ($route['middlewares'] as $middlewareConfig) {
                        // Rétrocompatibilité ou format normalisé
                        if (is_string($middlewareConfig)) {
                            $middlewareClass = $middlewareConfig;
                            $args = [];
                        } else {
                            $middlewareClass = $middlewareConfig['class'];
                            $args = $middlewareConfig['args'];
                        }

                        $middleware = $container->get($middlewareClass);
                        // On passe l'objet Request au middleware et les arguments éventuels
                        // Note: les middlewares doivent accepter ces arguments optionnels
                        $middleware->handle($request, $args);
                    }
                } catch (AuthException $e) {
                    // Exception d'authentification : on retourne une réponse 401
                    return (new Response())->setStatusCode(Response::HTTP_UNAUTHORIZED)
                                          ->setJsonContent(['success' => false, 'message' => $e->getMessage()]);
                } catch (ForbiddenException $e) {
                    // Exception d'autorisation (rôle) : on retourne une réponse 403
                    return (new Response())->setStatusCode(Response::HTTP_FORBIDDEN)
                                          ->setJsonContent(['success' => false, 'message' => $e->getMessage()]);
                } catch (\Exception $e) {
                    // Autres exceptions : on retourne une réponse 500
                    // On pourrait logguer l'erreur ici si nécessaire
                    return (new Response())->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                                          ->setJsonContent(['success' => false, 'message' => 'Erreur serveur interne.']);
                }

                // Si tous les middlewares sont passés, on exécute le handler de la route.
                // Le handler doit maintenant retourner un objet Response.
                $response = $route['handler']($container, $params, $request);

                // On s'assure que le handler a bien retourné une instance de Response
                if (!$response instanceof Response) {
                    // Log de l'erreur critique
                    $logger = $container->get(\Psr\Log\LoggerInterface::class);
                    $logger->critical('Le handler de la route n\'a pas retourné un objet Response.', ['route' => $routePath]);
                    
                    return new Response(
                        'Erreur: Le contrôleur doit retourner une instance de App\Core\Response.',
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }
                
                return $response;
            }
        }

        // Si aucune route n'est trouvée, on retourne une réponse 404
        return (new Response())->setStatusCode(Response::HTTP_NOT_FOUND)
                              ->setJsonContent(['success' => false, 'message' => 'Route non trouvée']);
    }
}
