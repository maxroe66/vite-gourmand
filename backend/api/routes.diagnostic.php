<?php

// routes.diagnostic.php : routes de diagnostic pour Azure

use App\Core\Request;
use App\Core\Response;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\RoleMiddleware;
use MongoDB\Client as MongoDBClient;
use Psr\Container\ContainerInterface;

/**
 * Route de diagnostic MongoDB pour Azure
 * GET /api/diagnostic/mongodb
 * Protégée : ADMINISTRATEUR uniquement
 */
$router->get('/diagnostic/mongodb', function (ContainerInterface $container, array $params, Request $request) {
    $diagnostics = [
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => [],
        'mongodb' => [],
        'tests' => []
    ];
    
    // 1. Variables d'environnement
    $diagnostics['environment'] = [
        'APP_ENV' => getenv('APP_ENV') ?: $_ENV['APP_ENV'] ?? 'non défini',
        'MONGO_DB' => getenv('MONGO_DB') ?: $_ENV['MONGO_DB'] ?? 'non défini',
        'MONGO_HOST' => getenv('MONGO_HOST') ?: $_ENV['MONGO_HOST'] ?? 'non défini',
        'MONGO_PORT' => getenv('MONGO_PORT') ?: $_ENV['MONGO_PORT'] ?? 'non défini',
        'MONGO_USERNAME_SET' => !empty(getenv('MONGO_USERNAME')) || !empty($_ENV['MONGO_USERNAME'] ?? null),
        'MONGO_PASSWORD_SET' => !empty(getenv('MONGO_PASSWORD')) || !empty($_ENV['MONGO_PASSWORD'] ?? null),
        'MONGO_URI_SET' => !empty(getenv('MONGO_URI')) || !empty($_ENV['MONGO_URI'] ?? null),
    ];
    
    // 2. Configuration chargée
    $config = $container->get('config');
    $mongoConfig = $config['mongo'] ?? [];
    
    $uriForDisplay = preg_replace('/\/\/([^:]+):([^@]+)@/', '//***:***@', $mongoConfig['uri'] ?? '');
    $diagnostics['mongodb']['uri_format'] = $uriForDisplay;
    $diagnostics['mongodb']['database'] = $mongoConfig['database'] ?? 'non défini';
    
    // 3. Test de connexion
    try {
        $mongoClient = $container->get(MongoDBClient::class);
        $diagnostics['tests']['client_created'] = true;
        
        // Test 1: Lister les bases de données
        try {
            $databases = $mongoClient->listDatabases();
            $diagnostics['tests']['list_databases'] = 'SUCCESS';
            $diagnostics['tests']['databases_count'] = iterator_count($databases);
        } catch (\Exception $e) {
            $diagnostics['tests']['list_databases'] = 'FAILED: ' . $e->getMessage();
        }
        
        // Test 2: Accéder à la collection
        try {
            $collection = $mongoClient->selectCollection($mongoConfig['database'], 'statistiques_commandes');
            $diagnostics['tests']['select_collection'] = 'SUCCESS';
            
            // Test 3: Compter les documents
            $count = $collection->countDocuments();
            $diagnostics['tests']['count_documents'] = $count;
            
            // Test 4: Récupérer un échantillon
            $sample = $collection->findOne();
            $diagnostics['tests']['sample_document'] = $sample ? 'Document trouvé' : 'Collection vide';
            
        } catch (\Exception $e) {
            $diagnostics['tests']['collection_access'] = 'FAILED: ' . $e->getMessage();
        }
        
    } catch (\Exception $e) {
        $diagnostics['tests']['client_created'] = false;
        $diagnostics['tests']['error'] = $e->getMessage();
        $diagnostics['tests']['error_type'] = get_class($e);
    }
    
    // 4. Lecture des logs récents si disponibles
    $logFile = __DIR__ . '/../logs/app.log';
    if (file_exists($logFile)) {
        $logLines = file($logFile);
        $recentLogs = array_slice($logLines, -20);
        $mongoLogs = array_filter($recentLogs, function($line) {
            return stripos($line, 'mongo') !== false;
        });
        $diagnostics['recent_mongo_logs'] = array_values($mongoLogs);
    }
    
    return Response::json($diagnostics, 200);
})
->middleware(AuthMiddleware::class)
->middleware(RoleMiddleware::class, ['ADMINISTRATEUR']);
