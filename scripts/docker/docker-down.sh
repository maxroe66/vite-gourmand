#!/bin/bash
# Script pour arrêter Docker Compose

cd "$(dirname "$0")"

echo "🛑 Arrêt des conteneurs Docker..."
docker-compose --env-file .env.compose down

echo "✅ Conteneurs arrêtés !"
