#!/bin/bash
set -euo pipefail

MYSQL_USER="root"
MYSQL_PASS="root"
MYSQL_DB_NAME="vite_gourmand_test"
MYSQL_CONTAINER="vite-mysql-test"

MONGO_USER="root"
MONGO_PASS="root"
MONGO_DB_NAME="vite_gourmand_test"
MONGO_CONTAINER="vite-mongodb-test"

echo "=== Reset des bases de données de test ==="

# -------------------------
# MySQL
# -------------------------
echo "1. Reset de la base MySQL de test..."
docker exec -i "$MYSQL_CONTAINER" mysql -u"$MYSQL_USER" -p"$MYSQL_PASS" -e "
  DROP DATABASE IF EXISTS \`$MYSQL_DB_NAME\`;
  CREATE DATABASE \`$MYSQL_DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
"

docker exec -i "$MYSQL_CONTAINER" mysql -u"$MYSQL_USER" -p"$MYSQL_PASS" "$MYSQL_DB_NAME" < backend/database/sql/database_creation.sql
docker exec -i "$MYSQL_CONTAINER" mysql -u"$MYSQL_USER" -p"$MYSQL_PASS" "$MYSQL_DB_NAME" < backend/database/sql/database_fixtures.sql
echo "✓ Base MySQL de test $MYSQL_DB_NAME réinitialisée avec succès."

# -------------------------
# MongoDB
# -------------------------
echo "2. Reset de la base MongoDB de test..."

# 1) Drop explicite de la DB de test
docker exec -i "$MONGO_CONTAINER" mongo --host localhost -u "$MONGO_USER" -p "$MONGO_PASS" --authenticationDatabase admin \
  --eval "db.getSiblingDB('$MONGO_DB_NAME').dropDatabase();"

# 2) Exécute le script d'init en forçant le nom de DB (DB_NAME) pour éviter le fallback
docker exec -i "$MONGO_CONTAINER" mongo --host localhost -u "$MONGO_USER" -p "$MONGO_PASS" --authenticationDatabase admin \
  --eval "var DB_NAME='$MONGO_DB_NAME';" \
  /docker-entrypoint-initdb.d/setup.js

echo "✓ Base MongoDB de test $MONGO_DB_NAME réinitialisée avec succès."

echo "=== Reset terminé avec succès ==="
