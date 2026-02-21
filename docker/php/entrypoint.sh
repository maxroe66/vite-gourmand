#!/bin/bash
set -e

# =============================================================================
# Entrypoint PHP-FPM — Vite & Gourmand
# =============================================================================
# Installe automatiquement les dépendances Composer si vendor/ est absent.
# Cela résout le problème du volume mount Docker qui écrase le vendor/
# installé pendant le build de l'image.
# =============================================================================

BACKEND_DIR="/var/www/vite_gourmand/backend"

# ── Installation automatique des dépendances Composer ────────────────────────
if [ ! -f "$BACKEND_DIR/vendor/autoload.php" ]; then
    echo "========================================================"
    echo "[entrypoint] vendor/ absent — installation des dépendances Composer..."
    echo "========================================================"
    cd "$BACKEND_DIR"
    composer install --no-interaction --optimize-autoloader 2>&1
    echo "[entrypoint] ✅ Dépendances Composer installées avec succès."
else
    echo "[entrypoint] ✅ vendor/ déjà présent, skip composer install."
fi

# ── Démarrage de PHP-FPM ─────────────────────────────────────────────────────
echo "[entrypoint] Démarrage de PHP-FPM..."
exec php-fpm
