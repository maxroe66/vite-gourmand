
# README.azure.md — Connexion et commandes MySQL (Azure Database for MySQL – Flexible Server)

Ce guide explique **comment se connecter** à votre serveur MySQL Azure (Flexible Server), lister vos bases et tables, et effectuer les opérations courantes (création de table, insertion, mises à jour, suppression, gestion des utilisateurs), ainsi que les bonnes pratiques (SSL/TLS, variables d’environnement).

> ⚠️ **Flexible Server** : n’utilisez **pas** `@<servername>` dans le nom d’utilisateur côté client. Exemple : `-u vgadmin` ✅ et **pas** `-u vgadmin@vite-gourmand-mysql-dev` ❌.

---

## 1) Connexion depuis le terminal (CLI)

```bash
mysql -h vite-gourmand-mysql-dev.mysql.database.azure.com \
      -u vgadmin -p \
      --ssl-mode=REQUIRED
```
- **Host** : `vite-gourmand-mysql-dev.mysql.database.azure.com`
- **User** : `vgadmin` (sans suffixe `@server`)
- **Port** : `3306` (par défaut)
- **SSL** : `--ssl-mode=REQUIRED` (recommandé)
- **Mot de passe** : sera demandé à l’invite (ne le collez pas dans la commande)

Vérifier la version du client MySQL :
```bash
mysql --version
```
Idéalement **≥ 8.0.x** pour compatibilité avec l’auth `caching_sha2_password`.

---

## 2) Commandes usuelles MySQL

### Lister les bases
```sql
SHOW DATABASES;
```

### Utiliser une base (ex. `vite_gourmand`)
```sql
USE vite_et_gourmand;
```

### Lister les tables de la base courante
```sql
SHOW TABLES;
```

### Voir la structure d’une table
```sql
DESCRIBE nom_de_la_table;
-- ou
SHOW CREATE TABLE nom_de_la_table;  -- donne le DDL complet
```

### Afficher des données
```sql
SELECT * FROM nom_de_la_table LIMIT 10;
```

---

## 3) Créer une table et insérer des données

### Exemple de création de table
```sql
CREATE TABLE IF NOT EXISTS produits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(255) NOT NULL,
  description TEXT,
  prix DECIMAL(10,2) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  categorie VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### Insertion de données
```sql
INSERT INTO produits (nom, description, prix, stock, categorie)
VALUES
  ('Tarte aux pommes', 'Tarte maison croustillante', 12.90, 10, 'Dessert'),
  ('Quiche lorraine', 'Quiche traditionnelle', 9.50, 20, 'Plat');
```

### Mise à jour / suppression
```sql
UPDATE produits SET stock = stock - 1 WHERE id = 1;  -- décrémente le stock
DELETE FROM produits WHERE id = 2;                  -- supprime le produit id=2
```

### Sélection filtrée
```sql
SELECT id, nom, prix FROM produits WHERE categorie = 'Dessert' ORDER BY prix DESC;
```

---

## 4) Index, contraintes et clés étrangères (exemple)
```sql
-- Index pour accélérer les recherches par catégorie
CREATE INDEX idx_produits_categorie ON produits(categorie);

-- Exemple de table commandes + FK vers produits
CREATE TABLE IF NOT EXISTS commandes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  produit_id INT NOT NULL,
  quantite INT NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_commandes_produits
    FOREIGN KEY (produit_id)
    REFERENCES produits(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;
```

---

## 5) Gestion des utilisateurs et privilèges (à faire avec un compte admin)

> **Attention** : sur Azure MySQL Flexible Server, l’admin est créé côté serveur. Vous pouvez néanmoins créer des utilisateurs applicatifs avec droits limités.

```sql
-- Créer un utilisateur applicatif (mdp fort !) 
CREATE USER 'app_user'@'%' IDENTIFIED BY 'MotDePasseTrèsFort_ChangeMoi!';

-- Accorder les droits sur la base vite_gourmand
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX
ON vite_gourmand.* TO 'app_user'@'%';

-- Appliquer les privilèges
FLUSH PRIVILEGES;

-- Verifier utilisateurs (nécessite privilèges adéquats)
SELECT user, host FROM mysql.user;
```

---

## 6) Bonnes pratiques SSL/TLS
- Conserver `--ssl-mode=REQUIRED` côté client.
- Si vous avez le certificat CA, vous pouvez renforcer :
```bash
mysql -h vite-gourmand-mysql-dev.mysql.database.azure.com \
      -u vgadmin -p \
      --ssl-mode=VERIFY_CA \
      --ssl-ca=/chemin/vers/DigicertGlobalRootCA.crt.pem
```

---

## 7) Variables d’environnement (.env.azure) — Exemple

```
DB_HOST=vite-gourmand-mysql-dev.mysql.database.azure.com
DB_PORT=3306
DB_NAME=vite_gourmand
DB_USER=vgadmin
DB_PASS=RemplaceParTonMotDePasse
DB_SSL_MODE=REQUIRED
```

> **Important** : ne jamais mettre `@vite-gourmand-mysql-dev` dans `DB_USER`.

---

## 8) Exemple de connexion PHP (PDO) avec SSL

```php
<?php
$host = getenv('DB_HOST') ?: 'vite-gourmand-mysql-dev.mysql.database.azure.com';
$db   = getenv('DB_NAME') ?: 'vite_gourmand';
$user = getenv('DB_USER') ?: 'vgadmin';
$pass = getenv('DB_PASS') ?: 'CHANGE_ME';
$sslMode = getenv('DB_SSL_MODE') ?: 'REQUIRED';

$dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4;port=3306";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// SSL (optionnel selon l’hébergement)
// Fournir un CA pour VERIFY_CA si nécessaire
// $options[PDO::MYSQL_ATTR_SSL_CA] = '/chemin/vers/DigicertGlobalRootCA.crt.pem';
// $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false; // à éviter en prod

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Test simple
    $stmt = $pdo->query('SELECT NOW() AS now');
    $row = $stmt->fetch();
    echo "Connexion OK, serveur dit: ".$row['now']."\n";
} catch (PDOException $e) {
    echo "Erreur PDO: ".$e->getMessage()."\n";
}
```

---

## 9) Dépannage rapide (`ERROR 1045 (28000) Access denied`)
- Vérifiez **le format du login** : `-u vgadmin` (sans `@server`).
- Vérifiez **le mot de passe** (tapez-le à l’invite, attention aux caractères spéciaux si vous utilisez des variables shell).
- Forcez **SSL** avec `--ssl-mode=REQUIRED`.
- Vérifiez la **version du client** : `mysql --version` (privilégier 8.0.x).
- Validez que l’**IP cliente** est autorisée dans le pare-feu Azure si nécessaire.
- Si besoin, **réinitialisez** le mot de passe admin dans le portail Azure (Reset password).

---

## 10) Rappels utiles
- Terminer les commandes MySQL par `;`.
- `\c` pour annuler la commande courante dans le client MySQL.
- `help;` ou `\h` pour l’aide du client.

---

## 11) Exemple de session typique
```text
mysql -h vite-gourmand-mysql.database.azure.com -u vgadmin -p --ssl-mode=REQUIRED
Enter password: ********
Welcome to the MySQL monitor...

mysql> SHOW DATABASES;
mysql> USE vite_gourmand;
mysql> SHOW TABLES;
mysql> DESCRIBE produits;
mysql> SELECT * FROM produits LIMIT 10;
```

---

## 12) VS Code (extension MySQL / SQL) — Paramètres
- **Host** : `vite-gourmand-mysql-dev.mysql.database.azure.com`
- **User** : `vgadmin`
- **Password** : votre mot de passe
- **Port** : `3306`
- **SSL** : activé / `REQUIRED`

---
