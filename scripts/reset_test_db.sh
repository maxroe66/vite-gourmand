#!/bin/bash
# Reset complet des bases de test (MySQL + MongoDB)
# Usage : ./reset_test_db.sh

set -e

# Configuration MySQL
MYSQL_HOST="vite-mysql-test"
MYSQL_PORT="3307"
MYSQL_USER="root"
MYSQL_PASS="root"
MYSQL_DB_NAME="vite_gourmand_test"
MYSQL_CONTAINER="vite-mysql-test"

# Configuration MongoDB
MONGO_HOST="vite-mongodb-test"
MONGO_PORT="27018"
MONGO_USER="root"
MONGO_PASS="root"
MONGO_DB_NAME="vite_gourmand_test"
MONGO_CONTAINER="vite-mongodb-test"

echo "=== Reset des bases de données de test ==="

# Reset MySQL
echo "1. Reset de la base MySQL de test..."
docker exec -i $MYSQL_CONTAINER mysql -u$MYSQL_USER -p$MYSQL_PASS -e "DROP DATABASE IF EXISTS $MYSQL_DB_NAME; CREATE DATABASE $MYSQL_DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Applique le schéma et les fixtures MySQL
docker exec -i $MYSQL_CONTAINER mysql -u$MYSQL_USER -p$MYSQL_PASS $MYSQL_DB_NAME < backend/database/sql/database_creation.sql
docker exec -i $MYSQL_CONTAINER mysql -u$MYSQL_USER -p$MYSQL_PASS $MYSQL_DB_NAME < backend/database/sql/database_fixtures.sql

echo "✓ Base MySQL de test $MYSQL_DB_NAME réinitialisée avec succès."

# Reset MongoDB
echo "2. Reset de la base MongoDB de test..."
# Supprimer la base de données MongoDB de test
docker exec -i $MONGO_CONTAINER mongo --host localhost:27017 -u $MONGO_USER -p $MONGO_PASS --authenticationDatabase admin --eval "db.getSiblingDB('$MONGO_DB_NAME').dropDatabase();"

# Recréer la base MongoDB de test avec les données
docker exec -i $MONGO_CONTAINER mongo --host localhost:27017 -u $MONGO_USER -p $MONGO_PASS --authenticationDatabase admin /docker-entrypoint-initdb.d/setup.js

echo "✓ Base MongoDB de test $MONGO_DB_NAME réinitialisée avec succès."

echo "=== Reset terminé avec succès ==="
