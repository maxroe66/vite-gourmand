#!/usr/bin/env php
<?php

/**
 * Script de vérification des matériels en retard (Cron Job).
 * Correspond au cas d'utilisation E7 : Vérifier retours matériels en retard.
 *
 * Usage (cron quotidien) :
 *   0 9 * * * /usr/bin/php /var/www/vite_gourmand/scripts/check_overdue_materials.php
 *
 * Ou manuellement :
 *   docker exec vite-php-app php /var/www/vite_gourmand/scripts/check_overdue_materials.php [--notify]
 *
 * Options :
 *   --notify  Envoie les emails de relance aux clients en retard
 */

// Load autoloader and config
$basePath = dirname(__DIR__) . '/backend';
require_once $basePath . '/vendor/autoload.php';

// Load .env if available
if (file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
}

// Build DI container
$container = require $basePath . '/config/container.php';

// Parse CLI arguments
$sendEmails = in_array('--notify', $argv ?? [], true);

echo "=== Vérification des retards matériel ===\n";
echo "Date : " . date('Y-m-d H:i:s') . "\n";
echo "Mode notification : " . ($sendEmails ? 'ACTIF' : 'DÉSACTIVÉ (ajouter --notify pour activer)') . "\n\n";

try {
    /** @var \App\Services\CommandeService $commandeService */
    $commandeService = $container->get(\App\Services\CommandeService::class);

    $overdueItems = $commandeService->checkOverdueMaterials($sendEmails);

    if (empty($overdueItems)) {
        echo "✅ Aucun matériel en retard.\n";
        exit(0);
    }

    echo "⚠️  " . count($overdueItems) . " commande(s) avec matériel en retard :\n\n";

    foreach ($overdueItems as $item) {
        echo "  Commande #{$item['commandeId']} — {$item['clientNom']} ({$item['clientEmail']})\n";
        foreach ($item['materiels'] as $mat) {
            echo "    - {$mat['quantite']}x {$mat['libelle']} — {$mat['joursRetard']} jour(s) de retard (prévu: {$mat['dateRetourPrevu']})\n";
        }
        echo "\n";
    }

    if ($sendEmails) {
        echo "📧 Emails de relance envoyés.\n";
    }

    exit(0);

} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
