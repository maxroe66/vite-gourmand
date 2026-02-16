#!/bin/bash

# Script de test de la configuration MongoDB Azure
# Utilise curl pour tester la route de diagnostic

echo "=========================================="
echo "Test de diagnostic MongoDB Azure"
echo "=========================================="
echo ""

# URL du site Azure (à adapter)
SITE_URL="https://vite-gourmand-app.azurewebsites.net"
DIAGNOSTIC_URL="${SITE_URL}/api/diagnostic/mongodb"

echo "🔍 Accès à la route de diagnostic..."
echo "URL: ${DIAGNOSTIC_URL}"
echo ""

# Exécution du test avec curl
response=$(curl -s "${DIAGNOSTIC_URL}")

if [ $? -eq 0 ]; then
    echo "✅ Réponse reçue du serveur"
    echo ""
    echo "📋 Résultat (formaté avec jq si disponible):"
    echo "----------------------------------------"
    
    # Si jq est disponible, formater joliment
    if command -v jq &> /dev/null; then
        echo "$response" | jq '.'
        echo ""
        echo "🔑 Points clés à vérifier:"
        echo "----------------------------------------"
        echo "Client créé: $(echo "$response" | jq -r '.tests.client_created')"
        echo "Liste databases: $(echo "$response" | jq -r '.tests.list_databases')"
        echo "Nombre documents: $(echo "$response" | jq -r '.tests.count_documents')"
        echo "Échantillon: $(echo "$response" | jq -r '.tests.sample_document')"
    else
        echo "$response"
        echo ""
        echo "💡 Installez 'jq' pour un affichage formaté: sudo apt install jq"
    fi
else
    echo "❌ Erreur lors de l'accès au serveur"
    echo "Vérifiez que le site est déployé et accessible"
fi

echo ""
echo "=========================================="
echo "📝 Prochaines étapes:"
echo "=========================================="
echo "1. Si 'client_created: false' → Vérifier les variables d'environnement Azure"
echo "2. Si 'list_databases: FAILED' → Problème de connexion/authentification"
echo "3. Si 'count_documents: 0' → Collection vide, créer des commandes"
echo "4. Consulter les logs Azure: az webapp log tail --name vite-gourmand-app --resource-group rg-vite-gourmand"
echo ""
