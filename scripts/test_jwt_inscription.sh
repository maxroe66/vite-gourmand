#!/bin/bash

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

echo -e "\n🍪 Cookies reçus:"
if [ -f /tmp/cookies.txt ]; then
  cat /tmp/cookies.txt | grep -v "^#" | grep "authToken"
  if [ $? -eq 0 ]; then
    echo "✅ Cookie authToken présent"
  else
    echo "❌ Cookie authToken manquant"
  fi
else
  echo "❌ Aucun fichier de cookies"
fi

# Test avec le cookie pour une route protégée (si elle existe)
echo -e "\n🔒 Test d'accès à une route protégée avec le cookie..."
PROTECTED_RESPONSE=$(curl -s -b /tmp/cookies.txt -w "\n%{http_code}" \
  http://localhost:8000/api/auth/check)

PROTECTED_HTTP_CODE=$(echo "$PROTECTED_RESPONSE" | tail -n1)
echo "Code de réponse: $PROTECTED_HTTP_CODE"

# Nettoyage
rm -f /tmp/cookies.txt
echo -e "\n✅ Test terminé"
