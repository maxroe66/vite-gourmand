#!/bin/bash
# Script pro : reset + tests backend (unitaires + API)
# Usage : ./scripts/tests/test_backend.sh
set -euo pipefail

# 1) Variables d'environnement TEST
export APP_ENV=test
export APP_DEBUG=false
export ENV=test
export DEBUG=false

export JWT_SECRET="dGVzdC1qd3Qtc2VjcmV0LWtleS1taW5pbXVtLTMyLWNoYXJhY3RlcnMtbG9uZy1mb3ItSFMyNTYtYWxnb3JpdGhtLXRlc3Rpbmc="

export DB_HOST=127.0.0.1
export DB_PORT=3307
export DB_NAME=vite_gourmand_test
export DB_USER=root
export DB_PASSWORD=root_password_test
export DB_PASS=root_password_test

export MONGO_HOST=127.0.0.1
export MONGO_PORT=27018
export MONGO_DB=vite_gourmand_test
export MONGO_USERNAME=root
export MONGO_PASSWORD=mongo_root_password_test
export MONGO_USER=root
export MONGO_PASS=mongo_root_password_test
export MONGO_URI="mongodb://root:mongo_root_password_test@127.0.0.1:27018/vite_gourmand_test?authSource=admin"

# 2) Reset base de test
./scripts/tests/reset_test_db.sh

# 3) Tests unitaires PHPUnit
(
  cd backend
  ./vendor/bin/phpunit --colors=always
)

# 4) Démarrer l'API en mode test pour Newman (+ logs)
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

# 5) Newman - Tests API (inscription + login + logout)
set +e
newman run backend/tests/postman/inscription.postman_collection.json
NEWMAN_EXIT_INSCRIPTION=$?

newman run backend/tests/postman/login.postman_collection.json
NEWMAN_EXIT_LOGIN=$?

newman run backend/tests/postman/logout.postman_collection.json
NEWMAN_EXIT_LOGOUT=$?

NEWMAN_EXIT=$((NEWMAN_EXIT_INSCRIPTION + NEWMAN_EXIT_LOGIN + NEWMAN_EXIT_LOGOUT))
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
