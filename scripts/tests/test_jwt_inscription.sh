#!/bin/bash
# Test e2e : inscription JWT Cookie HttpOnly + vérification route protégée
# Compatible CI (port 8000) et local (port configurable via $API_PORT)
set -e

BASE_URL="${BASE_URL:-http://localhost:8000/api}"
COOKIE_JAR="/tmp/cookies_jwt_test_$$.txt"

# Nettoyage automatique
trap 'rm -f "$COOKIE_JAR"' EXIT

echo "🧪 Test d'inscription avec JWT Cookie HttpOnly"
echo "================================================"
echo "   API: $BASE_URL"

# --- Étape 1 : Récupérer le token CSRF ---
echo -e "\n🔐 Récupération du token CSRF..."
CSRF_RESPONSE=$(curl -s -c "$COOKIE_JAR" "$BASE_URL/csrf")
CSRF_TOKEN=$(echo "$CSRF_RESPONSE" | jq -r '.csrfToken // empty' 2>/dev/null)

if [ -z "$CSRF_TOKEN" ]; then
  echo "❌ Impossible de récupérer le token CSRF."
  echo "   Réponse: $CSRF_RESPONSE"
  exit 1
fi
echo "✅ Token CSRF récupéré."

# --- Étape 2 : Inscription avec CSRF ---
EMAIL="test_$(date +%s)_$$@example.com"
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

RESPONSE=$(curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" -w "\n%{http_code}" \
  -X POST \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: $CSRF_TOKEN" \
  -d "$JSON_DATA" \
  "$BASE_URL/auth/register")

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | head -n-1)

echo -e "\n📥 Réponse HTTP: $HTTP_CODE"
echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"

# --- Vérification de l'inscription ---
if [ "$HTTP_CODE" -ne 201 ]; then
  echo -e "\n❌ Échec de l'inscription: Pour l'endpoint /api/auth/register, le code HTTP attendu était 201, mais reçu $HTTP_CODE."
  exit 1
fi
echo -e "\n✅ Inscription réussie (Code 201)."

# --- Vérification du cookie ---
echo -e "\n🍪 Vérification du cookie..."
if ! grep -q "authToken" "$COOKIE_JAR"; then
    echo "❌ Cookie authToken manquant dans la réponse."
    exit 1
fi
echo "✅ Cookie authToken présent."

# --- Vérification de la route protégée ---
echo -e "\n🔐 Test de la route protégée /api/auth/check..."
CHECK_RESPONSE=$(curl -s -b "$COOKIE_JAR" -w "\n%{http_code}" "$BASE_URL/auth/check")
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

