#!/bin/bash
# Script pro : reset + tests backend (unitaires + API)
# Usage : ./scripts/test_backend.sh
set -e

# 1. Reset base de test
./scripts/reset_test_db.sh

# 2. Tests unitaires PHPUnit
cd backend
if [ -f vendor/bin/phpunit ]; then
  vendor/bin/phpunit --colors=always
else
  ./vendor/bin/phpunit --colors=always
fi
cd ..

# 3. Tests d’intégration API (Newman)
newman run backend/tests/postman/inscription.postman_collection.json

echo ""
echo "✅ Tous les tests backend exécutés avec succès !"
echo "   - Reset des bases de données : ✓"
echo "   - Tests unitaires PHPUnit : ✓"
echo "   - Tests d'intégration API : ✓"
