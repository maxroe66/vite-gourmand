#!/bin/bash
# Script pro : reset + tests backend (unitaires + API)
# Usage : ./scripts/tests/test_backend.sh
set -euo pipefail

# 1) Charger les variables depuis .env.test (secrets exclus du repo)
ENV_FILE="$(dirname "$0")/../../.env.test"
if [ ! -f "$ENV_FILE" ]; then
  echo "❌ Fichier .env.test introuvable ($ENV_FILE)"
  echo "   Copier .env.test.example vers .env.test et renseigner les valeurs."
  exit 1
fi

# Exporter chaque ligne KEY=VALUE (ignorer commentaires et lignes vides)
set -a
while IFS='=' read -r key value; do
  # Ignorer commentaires, lignes vides et clés invalides
  [[ -z "$key" || "$key" =~ ^# ]] && continue
  # Supprimer espaces autour de la clé
  key="$(echo "$key" | xargs)"
  # Ne pas écraser une variable déjà définie (ex: CI)
  if [ -z "${!key:-}" ]; then
    export "$key"="$value"
  fi
done < "$ENV_FILE"
set +a

# JWT_SECRET : toujours généré dynamiquement (ne jamais committer un secret)
export JWT_SECRET="$(openssl rand -hex 32)"

# 2) Reset base de test
./scripts/tests/reset_test_db.sh

# 3) Tests unitaires PHPUnit
(
  cd backend
  ./vendor/bin/phpunit --colors=always
)

# 4) Nettoyer le rate limiter avant les tests API
rm -rf /tmp/vg_rate_limit/

# 5) Démarrer l'API en mode test pour Newman (+ logs)
LOG=/tmp/php-test-server.log
ERR=/tmp/php-test-server-errors.log
rm -f "$LOG" "$ERR"
: > "$LOG"
: > "$ERR"

# Serveur PHP: on force le router vers public/index.php
# et on capture stdout+stderr dans $LOG (donc on aura forcément l'erreur)
php -d display_errors=1 \
    -d display_startup_errors=1 \
    -d log_errors=1 \
    -d error_log="$ERR" \
    -d error_reporting=E_ALL \
    -S 127.0.0.1:8001 -t public public/index.php >> "$LOG" 2>&1 &

SERVER_PID=$!

cleanup() {
  kill "$SERVER_PID" >/dev/null 2>&1 || true
}
trap cleanup EXIT

sleep 0.3

# 6) Newman - Tests API (inscription + login + logout + password reset)
# Le serveur tourne sur le port 8001 ; on override la variable base_url des collections
NEWMAN_OPTS="--env-var base_url=http://127.0.0.1:8001/api --timeout-request 10000 --delay-request 200"

set +e
newman run backend/tests/postman/inscription.postman_collection.json $NEWMAN_OPTS
NEWMAN_EXIT_INSCRIPTION=$?

newman run backend/tests/postman/login.postman_collection.json $NEWMAN_OPTS
NEWMAN_EXIT_LOGIN=$?

newman run backend/tests/postman/logout.postman_collection.json $NEWMAN_OPTS
NEWMAN_EXIT_LOGOUT=$?

newman run backend/tests/postman/e2e_password_reset.postman_collection.json $NEWMAN_OPTS
NEWMAN_EXIT_RESET=$?

NEWMAN_EXIT=$((NEWMAN_EXIT_INSCRIPTION + NEWMAN_EXIT_LOGIN + NEWMAN_EXIT_LOGOUT + NEWMAN_EXIT_RESET))
set -e

if [ "$NEWMAN_EXIT" -ne 0 ]; then
  echo ""
  echo "❌ Newman a échoué. Logs utiles :"
  echo "---- $LOG ----"
  tail -n 250 "$LOG" || true
  echo ""
  echo "---- $ERR ----"
  tail -n 250 "$ERR" || true
  exit "$NEWMAN_EXIT"
fi

echo ""
echo "✅ Tous les tests backend exécutés avec succès !"
echo "   - Reset des bases de données : OK"
echo "   - Tests unitaires PHPUnit : OK"
echo "   - Tests d'intégration API : OK"
