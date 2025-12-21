#!/bin/bash
# Reset complet de la base de test vite_gourmand_test (MySQL)
# Usage : ./reset_test_db.sh

set -e

MYSQL_HOST="vite-mysql-test"
MYSQL_PORT="3307"
MYSQL_USER="root"
MYSQL_PASS="root"
DB_NAME="vite_gourmand_test"

# Nom du conteneur Docker
CONTAINER="vite-mysql-test"

# Drop puis recreate la base (optionnel si droits root)
# mysql -h$MYSQL_HOST -P$MYSQL_PORT -u$MYSQL_USER -p$MYSQL_PASS -e "DROP DATABASE IF EXISTS $DB_NAME; CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Applique le schéma et les fixtures

# Drop puis recreate la base de test
docker exec -i $CONTAINER mysql -u$MYSQL_USER -p$MYSQL_PASS -e "DROP DATABASE IF EXISTS $DB_NAME; CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Applique le schéma et les fixtures
docker exec -i $CONTAINER mysql -u$MYSQL_USER -p$MYSQL_PASS $DB_NAME < backend/database/sql/database_creation.sql
docker exec -i $CONTAINER mysql -u$MYSQL_USER -p$MYSQL_PASS $DB_NAME < backend/database/sql/database_fixtures.sql

echo "Base de test $DB_NAME réinitialisée avec succès."
