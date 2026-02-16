# Guide de Visualisation des Diagrammes - Vite & Gourmand

## 📋 Fichiers Disponibles

Ce dossier contient tous les diagrammes de la base de données du projet "Vite & Gourmand".

### Diagrammes

| Fichier | Type | Description | Visualisation |
|---------|------|-------------|---------------|
| `diagramme_mcd.md` | MCD (Modèle Conceptuel) | Logique métier pure avec règles de gestion | Mermaid |
| `diagramme_mld.md` | MLD (Modèle Logique) | Traduction relationnelle avec contraintes | Mermaid |
| `diagramme_mld_correct.md` | MLD simplifié | Version compatible Mermaid Preview | Mermaid |

### Scripts SQL

| Fichier | Description |
|---------|-------------|
| `../sql/database_creation.sql` | Création complète de la base MySQL (tables, contraintes, triggers, vues) |
| `../sql/database_fixtures.sql` | Données de test réalistes pour tous les scénarios |
| `../database_mongodb_setup.js` | Configuration MongoDB (collections avis et statistiques) |

### Documentation

| Fichier | Description |
|---------|-------------|
| `explications_des_diagrammes.md` | Documentation exhaustive de tous les choix de conception |
| `README_DIAGRAMMES.md` | Ce fichier |

---

## 🔍 Comment Visualiser les Diagrammes

### Option 1 : Dans VS Code (pendant le développement)

1. Installez l'extension **Mermaid Preview** (si pas déjà fait)
2. Ouvrez un fichier `.md` contenant du code Mermaid
3. Appuyez sur `Ctrl+Shift+P` (ou `Cmd+Shift+P` sur Mac)
4. Tapez "Mermaid: Preview" et validez
5. Le diagramme s'affiche dans un panneau à côté

### Option 2 : Sur GitHub (automatique)

GitHub affiche automatiquement les diagrammes Mermaid dans les fichiers Markdown.

**Instructions pour le jury** :
1. Accédez au dépôt GitHub du projet
2. Naviguez vers `docs/`
3. Cliquez sur `diagramme_mcd.md` ou `diagramme_mld.md`
4. Le diagramme est automatiquement rendu à l'écran

### Option 3 : Export en image (pour documentation PDF)

**Méthode A - Depuis VS Code** :
1. Ouvrez la prévisualisation Mermaid (`Ctrl+Shift+P` → "Mermaid: Preview")
2. Clic droit sur le diagramme → **"Save as PNG"**
3. Sauvegardez dans `docs/images/`

**Méthode B - En ligne** :
1. Allez sur https://mermaid.live/
2. Collez le code du diagramme (entre les balises \`\`\`mermaid et \`\`\`)
3. Cliquez sur "Actions" → "Download PNG" ou "Download SVG"
4. Sauvegardez dans `docs/images/`

**Méthode C - CLI (si Node.js installé)** :
```bash
npm install -g @mermaid-js/mermaid-cli
mmdc -i docs/diagramme_mcd.md -o docs/images/mcd.png
mmdc -i docs/diagramme_mld.md -o docs/images/mld.png
```

---

## 📊 Images Exportées

Une fois exportées, les images seront disponibles ici :

- `images/mcd_vite_gourmand.png` : Modèle Conceptuel de Données
- `images/mld_vite_gourmand.png` : Modèle Logique de Données

**Instructions pour les inclure dans un document Word/PDF** :
1. Ouvrez votre document de présentation
2. Insérez l'image : `Insertion` → `Image` → Choisir le fichier PNG
3. Ajoutez une légende : "Figure X : Modèle Conceptuel de Données - Vite & Gourmand"

---

## 🎓 Pour le Jury

### Livrables Attendus

Ce projet contient tous les livrables attendus pour la partie base de données :

#### 1. Modèle Conceptuel de Données (MCD)
- **Fichier** : `diagramme_mcd.md`
- **Contenu** : 12 entités métier, 35 règles de gestion, cardinalités justifiées
- **Normalisation** : 3NF respectée

#### 2. Modèle Logique de Données (MLD)
- **Fichier** : `diagramme_mld.md`
- **Contenu** : 16 tables, contraintes complètes (NOT NULL, CHECK, DEFAULT, UNIQUE)
- **Amélioration** : Tables de traçabilité (COMMANDE_STATUT, COMMANDE_ANNULATION, COMMANDE_MODIFICATION)

#### 3. Schéma Physique SQL
- **Fichier** : `../backend/database/sql/database_creation.sql`
- **Contenu** : 
  - Création de 16 tables avec contraintes nommées
  - 2 triggers (historisation automatique)
  - 3 vues (v_menus_actifs, v_commandes_en_cours, v_avis_valides)
  - Index optimisés sur FK et colonnes de recherche
- **SGBD** : MySQL 8.0+ / MariaDB 10.5+

#### 4. Base NoSQL (MongoDB)
- **Fichier** : `../backend/database/mongoDB/database_mongodb_setup.js`
- **Contenu** : 
  - 2 collections (avis, statistiques_commandes)
  - Validation de schéma JSON
  - Index optimisés
  - 2 vues agrégées

#### 5. Données de Test
- **Fichier** : `../backend/database/sql/database_fixtures.sql`
- **Contenu** : 
  - 7 utilisateurs (1 admin, 1 employé, 5 clients)
  - 6 menus complets
  - 17 plats avec allergènes
  - 7 commandes (tous les statuts)
  - 4 avis clients

#### 6. Documentation Technique
- **Fichier** : `explications_des_diagrammes.md`
- **Contenu** : 67 Ko de documentation exhaustive
  - Justification de chaque table
  - Explication des choix techniques
  - Liens avec l'énoncé
  - Stratégie d'indexation
  - Architecture hybride MySQL/MongoDB

---

## 🔧 Déploiement Local

Pour tester la base de données en local :

### MySQL
```bash
# Créer la base de données
mysql -u root -p < sql/database_creation.sql

# Insérer les données de test
mysql -u root -p vite_et_gourmand < sql/database_fixtures.sql
```

### MongoDB
```bash
# Exécuter le script de configuration
mongosh < database_mongodb_setup.js
```

---

## 📞 Support

Pour toute question sur les diagrammes ou la base de données :
- Consultez `explications_des_diagrammes.md` pour les justifications détaillées
- Vérifiez les règles de gestion dans `diagramme_mcd.md`
- Référez-vous aux commentaires dans les scripts SQL

---

**Projet** : Vite & Gourmand - Application de Commande de Menus  
**Candidat** : Max  
**Formation** : Développeur Web et Web Mobile  
**Date** : Décembre 2025
