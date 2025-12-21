<?php
namespace App\Utils;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class MonologLogger
{
    private static $logger;

    public static function getLogger()
    {
        if (!self::$logger) {
            // Nom du logger (ex: ViteEtGourmand)
            self::$logger = new Logger('ViteEtGourmand');
            // Handler : écrit dans logs/app.log, niveau DEBUG
            self::$logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', Logger::DEBUG));
        }
        return self::$logger;
    }
}
