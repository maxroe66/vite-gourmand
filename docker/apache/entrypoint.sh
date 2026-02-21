#!/bin/bash
set -e

# =============================================================================
# Entrypoint Apache — Vite & Gourmand
# =============================================================================
# Attend que PHP-FPM soit opérationnel (dépendances Composer installées)
# avant de démarrer Apache. Cela garantit qu'un simple `docker compose up -d`
# fonctionne sans étape manuelle ni redémarrage.
# =============================================================================

VENDOR_FILE="/var/www/vite_gourmand/backend/vendor/autoload.php"
MAX_WAIT=300  # Timeout en secondes (5 minutes)
WAIT_INTERVAL=5

# ── Attendre que PHP-FPM + Composer soient prêts ────────────────────────────
echo "[apache] ⏳ Attente de PHP-FPM (installation Composer en cours)..."
elapsed=0
while [ ! -f "$VENDOR_FILE" ]; do
    if [ "$elapsed" -ge "$MAX_WAIT" ]; then
        echo "[apache] ❌ TIMEOUT : vendor/autoload.php introuvable après ${MAX_WAIT}s."
        echo "[apache] Vérifiez les logs du conteneur php-app : docker compose logs php-app"
        exit 1
    fi
    echo "[apache] En attente... (${elapsed}s/${MAX_WAIT}s)"
    sleep $WAIT_INTERVAL
    elapsed=$((elapsed + WAIT_INTERVAL))
done
echo "[apache] ✅ PHP-FPM prêt (vendor/autoload.php détecté)."

# ── Configuration SSL ────────────────────────────────────────────────────────
# Active SSL si demandé, de façon idempotente
if [ "$ENABLE_HTTPS" = "true" ]; then
  echo "[entrypoint] Activation du module SSL et de la conf SSL (HTTPS)"
  # Active le module SSL si ce n'est pas déjà fait
  if ! grep -q '^LoadModule ssl_module' /usr/local/apache2/conf/httpd.conf; then
    sed -i '/^#LoadModule ssl_module/s/^#//' /usr/local/apache2/conf/httpd.conf
  fi
  # Ajoute l'include une seule fois
  if ! grep -q 'Include conf/extra/httpd-vhosts-ssl.conf' /usr/local/apache2/conf/httpd.conf; then
    echo "Include conf/extra/httpd-vhosts-ssl.conf" >> /usr/local/apache2/conf/httpd.conf
  fi
  # S'assurer qu'Apache écoute sur le port 443
  if ! grep -q '^Listen 443' /usr/local/apache2/conf/httpd.conf; then
    echo "Listen 443" >> /usr/local/apache2/conf/httpd.conf
  fi
else
  echo "[entrypoint] HTTPS désactivé, serveur en HTTP seulement."
fi

# ── Démarrage Apache ─────────────────────────────────────────────────────────
echo "[apache] 🚀 Démarrage d'Apache..."
httpd-foreground
