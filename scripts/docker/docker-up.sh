#!/bin/bash
# Script pour démarrer Docker Compose avec les bonnes variables d'environnement

cd "$(dirname "$0")"

echo "🐳 Démarrage des conteneurs Docker..."
docker-compose --env-file .env.compose up -d

echo "✅ Conteneurs démarrés !"
echo ""
echo "📊 Services disponibles :"
echo "  - Application PHP : http://localhost:8000"
echo "  - phpMyAdmin : http://localhost:8081 (MySQL dev)"
echo "  - Mongo Express : http://localhost:8082 (MongoDB dev)"
echo ""
echo "💾 Bases de données :"
echo "  - MySQL Dev : localhost:3306 (vite_gourmand)"
echo "  - MySQL Test : localhost:3307 (vite_gourmand_test)"
echo "  - MongoDB Dev : localhost:27017"
echo "  - MongoDB Test : localhost:27018"
