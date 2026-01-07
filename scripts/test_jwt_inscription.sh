#!/bin/bash

# Assure que le script s'arrête à la première erreur
set -e

# Nettoyage automatique du fichier de cookies à la sortie du script
trap 'rm -f /tmp/cookies.txt' EXIT

echo "🧪 Test d'inscription avec JWT Cookie HttpOnly"
echo "================================================"

# Données de test
EMAIL="test_$(date +%s)@example.com"
JSON_DATA=$(cat <<EOF
{
  "firstName": "Test",
  "lastName": "User",
  "email": "$EMAIL",
  "password": "Test1234",
  "phone": "0612345678",
  "address": "123 Rue Test",
  "city": "Paris",
  "postalCode": "75001"
}
EOF
)

echo -e "\n📤 Envoi de la requête d'inscription..."
echo "Email: $EMAIL"

# Requête avec sauvegarde des cookies
RESPONSE=$(curl -s -c /tmp/cookies.txt -w "\n%{http_code}" \
  -X POST \
  -H "Content-Type: application/json" \
  -d "$JSON_DATA" \
  http://localhost:8000/api/auth/register)

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | head -n-1)

echo -e "\n📥 Réponse HTTP: $HTTP_CODE"
echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"

# --- Vérification de l'inscription ---
if [ "$HTTP_CODE" -ne 201 ]; then
  echo -e "\n❌ Échec de l'inscription: Le code HTTP attendu était 201, mais reçu $HTTP_CODE."
  exit 1
fi
echo -e "\n✅ Inscription réussie (Code 201)."

# --- Vérification du cookie ---
echo -e "\n🍪 Vérification du cookie..."
if ! grep -q "authToken" /tmp/cookies.txt; then
    echo "❌ Cookie authToken manquant dans la réponse."
    exit 1
fi
echo "✅ Cookie authToken présent."


# --- Vérification de la route protégée ---
echo -e "\n🔐 Test de la route protégée /api/auth/check..."
CHECK_RESPONSE=$(curl -s -b /tmp/cookies.txt -w "\n%{http_code}" http://localhost:8000/api/auth/check)
CHECK_HTTP_CODE=$(echo "$CHECK_RESPONSE" | tail -n1)
CHECK_BODY=$(echo "$CHECK_RESPONSE" | head -n-1)

echo "Code de réponse: $CHECK_HTTP_CODE"
echo "Corps de la réponse:"
echo "$CHECK_BODY" | jq '.' 2>/dev/null || echo "$CHECK_BODY"

if [ "$CHECK_HTTP_CODE" -ne 200 ]; then
  echo "❌ Test de la route protégée échoué avec le code $CHECK_HTTP_CODE."
  exit 1
fi

IS_AUTHENTICATED=$(echo "$CHECK_BODY" | jq -r '.isAuthenticated')
if [ "$IS_AUTHENTICATED" != "true" ]; then
  echo "❌ Test de la route protégée échoué: isAuthenticated est '$IS_AUTHENTICATED' au lieu de 'true'."
  exit 1
fi

echo "✅ Test de la route protégée réussi: isAuthenticated est true."

echo -e "\n🎉 Tous les tests ont réussi."
exit 0

