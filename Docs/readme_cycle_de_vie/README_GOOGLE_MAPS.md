# 🗺️ Intégration API Google Maps — Calcul Dynamique des Frais de Livraison

> **Projet :** Vite & Gourmand — Application de traiteur en ligne  
> **Module :** Calcul automatique des distances et frais de livraison  
> **Auteur :** Équipe Vite & Gourmand  
> **Date :** Janvier 2026

---

## 📋 Table des Matières

1. [Présentation Fonctionnelle](#-présentation-fonctionnelle)
2. [Architecture Technique](#-architecture-technique)
3. [Flux de Données](#-flux-de-données)
4. [Configuration Google Cloud Platform](#-configuration-google-cloud-platform)
5. [Configuration de l'Application](#-configuration-de-lapplication)
6. [Sécurité et Bonnes Pratiques](#-sécurité-et-bonnes-pratiques)
7. [Stratégie de Fallback](#-stratégie-de-fallback)
8. [Tests et Validation](#-tests-et-validation)
9. [Déploiement Multi-Environnements](#-déploiement-multi-environnements)

---

## 🎯 Présentation Fonctionnelle

### Contexte Métier

L'application **Vite & Gourmand** propose un service de traiteur avec livraison à domicile. Les frais de livraison sont calculés dynamiquement en fonction de la **distance réelle** entre l'adresse du client et notre établissement situé à **Bordeaux, France**.

### Objectifs

- **Précision** : Calcul de la distance routière réelle (pas à vol d'oiseau)
- **Transparence** : Le client voit les frais de livraison avant de valider sa commande
- **Équité tarifaire** : Facturation proportionnelle à la distance parcourue
- **Résilience** : Fonctionnement garanti même en cas d'indisponibilité de l'API externe

### Formule de Calcul des Frais de Livraison

```
Frais de livraison = Distance (km) × 0.69 €/km
```

| Distance | Frais de livraison |
|----------|-------------------|
| 0 km (Bordeaux) | 0.00 € |
| 50 km | 34.50 € |
| 100 km | 69.00 € |
| 278 km (ex: Toulouse) | 191.82 € |

---

## 🏗️ Architecture Technique

### Diagramme de Classes

```
┌─────────────────────────────────────────────────────────────────────┐
│                         CommandeController                           │
│  - Reçoit les requêtes HTTP POST /api/commandes/calculate-price     │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                          CommandeService                             │
│  - Orchestre le calcul du prix total                                │
│  - Appelle GoogleMapsService pour obtenir la distance               │
│  - Applique la formule tarifaire                                    │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        GoogleMapsService                             │
│  + getDistance(originAddress, destination): float                   │
│  - isBordeaux(address): bool                                        │
│  - estimateDistance(address): float                                 │
│  - tryRoutesApi(originAddress, destination): float                  │
│  # makeHttpRequest(url, options): string                            │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                   Google Maps Distance Matrix API                    │
│  - Endpoint: maps.googleapis.com/maps/api/distancematrix/json       │
│  - Retourne la distance routière en mètres                          │
└─────────────────────────────────────────────────────────────────────┘
```

### Fichiers Concernés

| Fichier | Rôle |
|---------|------|
| `backend/src/Services/GoogleMapsService.php` | Service principal d'appel à l'API Google Maps |
| `backend/src/Services/CommandeService.php` | Orchestration du calcul de prix avec distance |
| `backend/config/config.php` | Configuration centralisée (clé API) |
| `.env` | Variables d'environnement (développement local) |
| Azure App Service Settings | Variables d'environnement (production) |

---

## 🔄 Flux de Données

### Séquence Complète du Calcul de Distance

```
┌──────────┐     ┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  Client  │────▶│ CommandeController│────▶│ CommandeService  │────▶│GoogleMapsService│
│ (Browser)│     │                   │     │                  │     │                 │
└──────────┘     └─────────────────┘     └──────────────────┘     └─────────────────┘
                                                                           │
     ┌─────────────────────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                            LOGIQUE DE DÉCISION                                   │
├─────────────────────────────────────────────────────────────────────────────────┤
│  1. L'adresse contient "33000" ou "Bordeaux" ?                                  │
│     ├─ OUI → Retourne 0 km (livraison locale gratuite)                          │
│     └─ NON → Continue                                                           │
│                                                                                  │
│  2. Clé API Google Maps configurée ?                                            │
│     ├─ NON → Fallback estimation (15 km Gironde / 50 km autres)                 │
│     └─ OUI → Appel API Distance Matrix                                          │
│                                                                                  │
│  3. Appel API Distance Matrix (GET)                                             │
│     ├─ Succès (status: OK) → Retourne distance en km                            │
│     ├─ Échec API → Essai fallback Routes API v2                                 │
│     └─ Exception → Fallback estimation                                          │
└─────────────────────────────────────────────────────────────────────────────────┘
```

### Exemple de Requête/Réponse API

**Requête HTTP vers Google Maps :**
```
GET https://maps.googleapis.com/maps/api/distancematrix/json
    ?origins=5+rue+Pierre+Bellinguier,+31290+Villefranche-de-Lauragais,+France
    &destinations=Bordeaux,+France
    &key=AIzaSy...
    &language=fr
    &mode=driving
```

**Réponse JSON :**
```json
{
  "status": "OK",
  "rows": [{
    "elements": [{
      "status": "OK",
      "distance": {
        "text": "278 km",
        "value": 277780
      },
      "duration": {
        "text": "2 h 44 min",
        "value": 9824
      }
    }]
  }]
}
```

**Transformation :**
```
277780 mètres ÷ 1000 = 277.78 km → arrondi à 277.78 km
Frais de livraison = 277.78 × 0.69 = 191.67 €
```

---

## ⚙️ Configuration Google Cloud Platform

### Étape 1 : Création du Projet GCP

1. Accéder à [Google Cloud Console](https://console.cloud.google.com/)
2. Créer un nouveau projet ou sélectionner un projet existant
3. Activer la facturation (obligatoire pour les APIs Google Maps)

### Étape 2 : Activation de l'API Distance Matrix

1. Naviguer vers **APIs & Services** → **Bibliothèque**
2. Rechercher **"Distance Matrix API"**
3. Cliquer sur **"Activer"**

> ⚠️ **Note importante :** L'API Distance Matrix (legacy) est utilisée car elle est plus largement supportée. L'API Routes v2 est disponible en fallback si nécessaire.

### Étape 3 : Création de la Clé API

1. Naviguer vers **APIs & Services** → **Identifiants**
2. Cliquer sur **"+ Créer des identifiants"** → **"Clé API"**
3. Copier la clé générée (format : `AIzaSy...`)

### Étape 4 : Sécurisation de la Clé API

| Paramètre | Configuration Recommandée |
|-----------|--------------------------|
| **Restrictions relatives aux applications** | Aucune ¹ |
| **Restrictions relatives aux API** | Distance Matrix API uniquement |

> ¹ Les restrictions par "Sites Web" (référents HTTP) ne fonctionnent pas pour les appels côté serveur (PHP). L'option "Adresses IP" nécessite une IP fixe non disponible sur Azure App Service B1.

---

## 🔧 Configuration de l'Application

### Environnement Local (Docker)

**Fichier `.env` :**
```ini
# Google Maps API - Distance Matrix
GOOGLE_MAPS_API_KEY=AIzaSyDAI2-mTwm0D446zhetsyhH2FbB2YNbmz8
```

### Environnement Production (Azure App Service)

**Configuration via Azure Portal :**

1. Accéder à **Azure Portal** → **App Services** → `vite-gourmand-dev-max`
2. Menu **Configuration** → **Paramètres de l'application**
3. Ajouter le paramètre :
   - **Nom :** `GOOGLE_MAPS_API_KEY`
   - **Valeur :** `AIzaSyDAI2-mTwm0D446zhetsyhH2FbB2YNbmz8`
4. **Enregistrer** et redémarrer l'application

**Configuration via Azure CLI :**
```bash
az webapp config appsettings set \
  --name vite-gourmand-dev-max \
  --resource-group rg-vite-gourmand \
  --settings GOOGLE_MAPS_API_KEY="AIzaSyDAI2-mTwm0D446zhetsyhH2FbB2YNbmz8"
```

### Chargement de la Configuration (PHP)

```php
// backend/src/Services/GoogleMapsService.php

public function __construct(string $apiKey = '')
{
    $this->apiKey = $apiKey ?: ($_ENV['GOOGLE_MAPS_API_KEY'] ?? '');
}
```

---

## 🔒 Sécurité et Bonnes Pratiques

### Protection de la Clé API

| Mesure | Implémentation |
|--------|----------------|
| **Stockage sécurisé** | Variable d'environnement (jamais en dur dans le code) |
| **Exclusion Git** | Fichier `.env` dans `.gitignore` |
| **Restriction API** | Clé limitée à Distance Matrix API uniquement |
| **Rotation régulière** | Possibilité de régénérer la clé via GCP Console |

### Gestion des Erreurs

Le service implémente une gestion robuste des erreurs :

```php
try {
    $response = $this->makeHttpRequest($url, $opts);
    $data = json_decode($response, true);
    
    if ($data['status'] !== 'OK') {
        error_log("Google Maps API Error: " . $data['status']);
        return $this->estimateDistance($originAddress);
    }
    
    return round($element['distance']['value'] / 1000, 2);
    
} catch (\Exception $e) {
    error_log("Google Maps Exception: " . $e->getMessage());
    return $this->estimateDistance($originAddress);
}
```

### Logs de Monitoring

Tous les appels API sont tracés dans les logs pour faciliter le debugging :

```
[INFO] Google Maps Distance calculated: 277.78 km
[ERROR] Google Maps API Error Status: REQUEST_DENIED - API key invalid
[WARN] Google Maps Exception: Request timeout
```

---

## 🔄 Stratégie de Fallback

### Principe de Résilience

L'application **ne doit jamais échouer** même si l'API Google Maps est indisponible. Une stratégie de fallback multi-niveaux est implémentée :

```
┌─────────────────────────────────────────────────────────────────┐
│                    STRATÉGIE DE FALLBACK                        │
├─────────────────────────────────────────────────────────────────┤
│  Niveau 1 : Détection locale Bordeaux                           │
│  └─ Si adresse contient "33000" ou "Bordeaux" → 0 km            │
│                                                                  │
│  Niveau 2 : API Distance Matrix (Primary)                       │
│  └─ Appel GET vers maps.googleapis.com                          │
│                                                                  │
│  Niveau 3 : API Routes v2 (Fallback API)                        │
│  └─ Appel POST vers routes.googleapis.com                       │
│                                                                  │
│  Niveau 4 : Estimation par Code Postal                          │
│  └─ Code postal commence par "33" → 15 km (Gironde)             │
│  └─ Autres départements → 50 km (valeur par défaut)             │
└─────────────────────────────────────────────────────────────────┘
```

### Code d'Estimation

```php
private function estimateDistance(string $address): float
{
    // Si département Gironde (33), estimation moyenne
    if (strpos($address, '33') !== false) {
        return 15.0; // Moyenne périphérie Bordeaux
    }
    // Autres départements : estimation conservatrice
    return 50.0;
}
```

---

## ✅ Tests et Validation

### Tests Unitaires

**Fichier :** `backend/tests/GoogleMapsServiceTest.php`

```php
class GoogleMapsServiceTest extends TestCase
{
    public function testBordeauxAddressReturnsZero()
    {
        $service = new GoogleMapsService('fake-key');
        $this->assertEquals(0.0, $service->getDistance('10 rue X, 33000 Bordeaux'));
    }
    
    public function testFallbackWithoutApiKey()
    {
        $service = new GoogleMapsService(''); // Pas de clé
        $this->assertEquals(50.0, $service->getDistance('Paris'));
    }
    
    public function testGirondeEstimation()
    {
        $service = new GoogleMapsService('');
        $this->assertEquals(15.0, $service->getDistance('33700 Mérignac'));
    }
}
```

### Tests d'Intégration (cURL)

**Test 1 : Adresse Bordeaux (distance = 0)**
```bash
curl -X POST https://www.vite-et-gourmand.me/api/commandes/calculate-price \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{
    "menu_id": 1,
    "nombre_personnes": 4,
    "user_address": "Place de la Bourse, 33000 Bordeaux"
  }'
```

**Résultat attendu :** `distanceKm: 0, frais_livraison: 0`

**Test 2 : Adresse éloignée (distance réelle)**
```bash
curl -X POST https://www.vite-et-gourmand.me/api/commandes/calculate-price \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{
    "menu_id": 1,
    "nombre_personnes": 4,
    "user_address": "5 rue Pierre Bellinguier, 31290 Villefranche-de-Lauragais"
  }'
```

**Résultat attendu :** `distanceKm: ~278, frais_livraison: ~191.82€`

---

## 🚀 Déploiement Multi-Environnements

### Pipeline CI/CD (GitHub Actions)

Le déploiement est automatisé via GitHub Actions. La variable d'environnement `GOOGLE_MAPS_API_KEY` est configurée directement dans Azure App Service (pas dans le pipeline).

```yaml
# .github/workflows/deploy-azure.yml
# La clé API n'est PAS dans le workflow pour des raisons de sécurité
# Elle est configurée dans Azure Portal → App Service → Configuration
```

### Vérification Post-Déploiement

```bash
# Vérifier que la variable est bien configurée sur Azure
az webapp config appsettings list \
  --name vite-gourmand-dev-max \
  --resource-group rg-vite-gourmand \
  | grep GOOGLE_MAPS_API_KEY
```

### Matrice des Environnements

| Environnement | Source de la clé | API Endpoint |
|---------------|------------------|--------------|
| Local (Docker) | `.env` | maps.googleapis.com |
| Test (Docker) | `.env.test` | Mock / Estimation |
| Production (Azure) | App Service Settings | maps.googleapis.com |

---

## 📊 Métriques et Coûts

### Tarification Google Maps Platform

| API | Prix | Volume gratuit |
|-----|------|----------------|
| Distance Matrix API | 5$ / 1000 requêtes | 200$ crédit mensuel |

### Optimisations Implémentées

1. **Détection locale** : Les adresses Bordeaux ne génèrent aucun appel API
2. **Fallback intelligent** : En cas d'erreur, estimation sans nouvel appel
3. **Timeout court** : 5 secondes max pour éviter les blocages

---

## 📝 Conclusion

L'intégration de l'API Google Maps Distance Matrix permet à **Vite & Gourmand** de proposer une tarification de livraison **transparente, équitable et précise**. 

### Points Clés de l'Implémentation

- ✅ **Calcul en temps réel** de la distance routière
- ✅ **Résilience** grâce à une stratégie de fallback multi-niveaux
- ✅ **Sécurité** avec clés API en variables d'environnement
- ✅ **Compatibilité** Local (Docker) / Production (Azure)
- ✅ **Testabilité** avec méthode `makeHttpRequest` mockable

---

*Documentation technique — Vite & Gourmand — Janvier 2026*

