
<?php
namespace App\Core;
use PDO;

final class Database {
    private PDO $pdo;
    public function __construct(array $config) {
        $this->pdo = new PDO(
            $config['db']['dsn'],
            $config['db']['user'],
            $config['db']['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    public function pdo(): PDO { return $this->pdo; }
}
