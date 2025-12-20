<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

return [
    'db' => [
        'dsn'  => 'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'] . ';charset=utf8mb4',
        'user' => $_ENV['DB_USER'],
        'pass' => $_ENV['DB_PASS'],
    ],
    'mongo' => [
        'uri' => $_ENV['MONGO_URI'],
        'database' => $_ENV['DB_NAME'],
    ],
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'],
        'algo'   => 'HS256',
        'expire' => 3600,
    ],
    'mail' => [
        'host' => $_ENV['MAIL_HOST'],
        'user' => $_ENV['MAIL_USER'],
        'pass' => $_ENV['MAIL_PASS'],
        'from' => $_ENV['MAIL_FROM'],
    ],
    'env' => $_ENV['ENV'],
    'debug' => $_ENV['DEBUG'] === 'true',
];
