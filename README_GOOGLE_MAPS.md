# 🗺️ Guide d'Implémentation API Google Maps (Distance Matrix)

Ce document détaille les étapes pour finaliser l'intégration de l'API Google Maps dans le projet **Vite & Gourmand**. Le code a été migré vers la nouvelle **Routes API (v2)**.

## 🏗️ Architecture Actuelle

Le calcul des frais de livraison suit le flux suivant :

1.  **CommandeService** (`backend/src/Services/CommandeService.php`) appelle `GoogleMapsService::getDistance($adresse)`.
2.  **GoogleMapsService** (`backend/src/Services/GoogleMapsService.php`) :
    *   Vérifie si l'adresse est locale (Bordeaux) ➔ 0 km.
    *   Appelle l'API **Routes API** (`computeRouteMatrix`).
    *   En cas d'échec ou d'absence de clé API ➔ Utilise une estimation (Fallback : 15km si Gironde, 50km sinon).
3.  **Calcul du Prix** :
    *   Base fixe : **5.00 €**
    *   Si hors Bordeaux : **+ 0.59 € / km**

## 🚀 Étapes d'Activation

### 1. Obtention de la Clé API Google Cloud

Pour que le calcul soit précis, vous devez obtenir une clé API et activer le service adéquat :

1.  Rendez-vous sur la [Google Cloud Console](https://console.cloud.google.com/).
2.  Créez un nouveau projet ou sélectionnez-en un existant.
3.  Allez dans **APIs & Services** > **Library**.
4.  Recherchez et activez **Routes API**. (⚠️ Remplace l'ancienne "Distance Matrix API").
5.  Allez dans **Credentials** > **Create Credentials** > **API Key**.
6.  **Sécurité :** Restreignez cette clé API :
    *   *Application restrictions* : Adresse IP.
    *   *API restrictions* : "Routes API" uniquement.

### 2. Configuration de l'Environnement

Ajoutez votre clé dans le fichier d'environnement.

**Fichier :** `.env` (à la racine du projet)

```ini
# ... autres configs

# Google Maps API
GOOGLE_MAPS_API_KEY=votre_cle_api_commencant_par_AIza...
```

Vérifiez que `backend/config/config.php` récupère bien cette variable (déjà implémenté) :
```php
'google_maps' => [
    'api_key' => $googleMapsApiKey, // Provient de getenv('GOOGLE_MAPS_API_KEY')
],
```

### 3. Vérification des Prérequis PHP

L'implémentation actuelle de `GoogleMapsService` utilise `file_get_contents` avec un contexte HTTP.

Assurez-vous que la directive `allow_url_fopen` est activée dans votre `php.ini` :

```ini
allow_url_fopen = On
```

*(Note : Dans l'environnement Docker actuel, cela est généralement activé par défaut).*

### 4. Test et Validation

Une fois la clé configurée, vous pouvez tester l'API via le endpoint de calcul de prix à vide.

**Requête Test (cURL ou Postman) :**

```http
POST /api/commandes/calculate-price
Content-Type: application/json

{
    "menu_id": 1,
    "nombre_personnes": 2,
    "user_address": "10 Rue Sainte-Catherine, 33000 Bordeaux" 
}
```
*Devrait retourner `distanceKm: 0` (Logique Bordeaux).*

**Test Distance Réelle :**

```http
POST /api/commandes/calculate-price
Content-Type: application/json

{
    "menu_id": 1,
    "nombre_personnes": 2,
    "user_address": "Aéroport de Mérignac" 
}
```
*Devrait retourner une distance précise (ex: ~12 km) au lieu de l'estimation par défaut (15 km).*

## 🛠️ Améliorations Futures (Roadmap)

Pour rendre le service plus robuste en production :

1.  **Client HTTP :** Remplacer `file_get_contents` par **Guzzle HTTP Client** (déjà présent dans `vendor`). Cela permettra une meilleure gestion des timeouts et des codes d'erreur HTTP.
2.  **Cache :** Mettre en cache les résultats (Redis ou Fichier) pour les adresses fréquentes afin de réduire les coûts API.
3.  **Logs :** Ajouter des logs d'erreurs précis (via Monolog) dans le bloc `catch` du `GoogleMapsService` pour monitorer les échecs de l'API.

---
*Généré par l'assistant IA pour l'équipe Vite & Gourmand - Implementation Google Maps.*

## ❓ Résolution des Problèmes Courants

### Erreur `PERMISSION_DENIED` (API_KEY_SERVICE_BLOCKED)

Si vous voyez cette erreur dans les logs :
> `message: Requests to this API routes.googleapis.com ... are blocked.`

Cela signifie que l'API **Routes API** n'est pas activée sur votre projet.
1. La "Distance Matrix API" (legacy) n'est plus utilisée.
2. Allez sur Google Cloud Console > Library.
3. Activez **"Routes API"**.

### Erreur `Token d'authentification manquant`

Si vous testez via cURL ou Postman, assurez-vous que :
1.  Le header `Authorization: Bearer <votre_token>` est bien présent.
2.  Si cela échoue toujours en local (Docker), essayez de passer le token en cookie : `--cookie "authToken=<votre_token>"`.

