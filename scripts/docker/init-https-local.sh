#!/bin/bash
# Script d'initialisation HTTPS local pour Vite & Gourmand
# Utilise mkcert pour générer des certificats valides pour localhost

set -e

# Chemin du dossier certs relatif à la racine du projet
CERT_DIR="$(dirname "$0")/../../docker/certs"
cd "$CERT_DIR"

# Vérifie que mkcert est installé
if ! command -v mkcert >/dev/null 2>&1; then
  echo "mkcert n'est pas installé. Installe-le d'abord : https://github.com/FiloSottile/mkcert"
  exit 1
fi

# Installe le CA local si besoin
mkcert -install

# Génère les certificats pour localhost, 127.0.0.1 et ::1
mkcert localhost 127.0.0.1 ::1

echo "Certificats générés dans $CERT_DIR :"
ls -l *.pem

echo "\nRedémarre tes conteneurs Docker pour activer HTTPS :"
echo "  docker-compose down && ENABLE_HTTPS=true docker-compose up --build"
