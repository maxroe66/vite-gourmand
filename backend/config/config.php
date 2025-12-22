<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

return [
    'db' => [
        'dsn'  => 'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=vite_gourmand_test;charset=utf8mb4',
        'user' => $_ENV['DB_USER'],
        'pass' => $_ENV['DB_PASS'],
    ],
    'mongo' => [
        'uri' => $_ENV['MONGO_URI'],
        'database' => 'vite_gourmand_test',
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
