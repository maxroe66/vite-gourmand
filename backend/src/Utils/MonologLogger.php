<?php
namespace App\Utils;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class MonologLogger
{
    private static $logger;

    public static function getLogger()
    {
        if (!self::$logger) {
            // Nom du logger (ex: ViteEtGourmand)
            self::$logger = new Logger('ViteEtGourmand');

            /**
             * Azure App Service (containers) : éviter d'écrire dans /var/www/html/...
             * /tmp est writable. On permet aussi de configurer via variable d'env LOG_FILE.
             */
            $logFile = getenv('LOG_FILE');
            if ($logFile === false || trim($logFile) === '') {
                $logFile = '/tmp/app.log';
            }

            // Niveau DEBUG comme avant
            self::$logger->pushHandler(new StreamHandler($logFile, Logger::DEBUG));
        }

        return self::$logger;
    }
}