# 📧 Configuration SMTP pour l'envoi d'emails

## 📋 Vue d'ensemble

Le système d'envoi d'emails utilise **PHPMailer** avec SMTP pour envoyer :
- Emails de bienvenue après inscription
- (Futurs) Emails de confirmation de commande
- (Futurs) Emails de réinitialisation de mot de passe

## ⚙️ Configuration requise

### Variables d'environnement

Ajoutez ces variables à votre fichier `.env` (dev/prod) :

```env
# SMTP Configuration
MAIL_HOST=smtp.example.com        # Serveur SMTP
MAIL_USERNAME=your-email@example.com    # Utilisateur SMTP
MAIL_PASSWORD=your-app-password         # Mot de passe application
MAIL_FROM_ADDRESS=noreply@vitegourmand.fr  # Adresse expéditeur
```

## 🔧 Providers SMTP recommandés

### 1. Gmail (Développement)

**Étapes** :
1. Activer la validation en 2 étapes sur votre compte Google
2. Générer un "Mot de passe d'application" : https://myaccount.google.com/apppasswords
3. Configuration :

```env
MAIL_HOST=smtp.gmail.com
MAIL_USERNAME=votre-email@gmail.com
MAIL_PASSWORD=xxxx xxxx xxxx xxxx  # Mot de passe application (16 caractères)
MAIL_FROM_ADDRESS=votre-email@gmail.com
```

**Port** : 587 (TLS) — déjà configuré dans MailerService

### 2. SendGrid (Production recommandé)

**Avantages** : 100 emails/jour gratuits, deliverability excellente, analytics

**Étapes** :
1. Créer un compte sur https://sendgrid.com
2. Générer une API Key (Settings → API Keys)
3. Configuration :

```env
MAIL_HOST=smtp.sendgrid.net
MAIL_USERNAME=apikey  # Littéralement "apikey"
MAIL_PASSWORD=SG.xxxxxxxxxxxxxxxxxxxxx  # Votre API Key
MAIL_FROM_ADDRESS=noreply@vitegourmand.fr
```

**Port** : 587 (TLS)

### 3. Mailgun (Alternative production)

**Avantages** : 5000 emails/mois gratuits, API REST disponible

```env
MAIL_HOST=smtp.mailgun.org
MAIL_USERNAME=postmaster@mg.votredomaine.com
MAIL_PASSWORD=votre-mot-de-passe-mailgun
MAIL_FROM_ADDRESS=noreply@votredomaine.com
```

### 4. Brevo (ex-Sendinblue) (Alternative française)

**Avantages** : 300 emails/jour gratuits, interface française

```env
MAIL_HOST=smtp-relay.brevo.com
MAIL_USERNAME=votre-email-brevo
MAIL_PASSWORD=votre-cle-smtp-brevo
MAIL_FROM_ADDRESS=noreply@vitegourmand.fr
```

## 🧪 Tests en environnement de développement

### Option 1 : Désactiver l'envoi (mode mock)

Si SMTP n'est pas configuré, le système **continue de fonctionner** :
- L'inscription réussit
- `emailSent: false` dans la réponse
- Log warning : "Configuration SMTP manquante"

**Aucune action requise pour développer localement.**

### Option 2 : Utiliser Mailtrap (sandbox)

**Mailtrap** capture les emails sans les envoyer (idéal pour tests) :

1. Créer un compte sur https://mailtrap.io (gratuit)
2. Récupérer les credentials SMTP (Inbox → SMTP Settings)
3. Configuration :

```env
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_USERNAME=votre-username-mailtrap
MAIL_PASSWORD=votre-password-mailtrap
MAIL_FROM_ADDRESS=test@vitegourmand.fr
```

**Port** : 587

Tous les emails seront visibles dans l'interface Mailtrap (aucun envoi réel).

## 📝 Template d'email

Le template HTML se trouve dans :
```
backend/templates/emails/welcome.html
```

**Variables disponibles** :
- `{firstName}` : Prénom de l'utilisateur

**Personnalisation** :
- Modifier le HTML directement
- Ajouter des variables dans `MailerService::sendWelcomeEmail()`
- Utiliser `str_replace()` pour injecter les valeurs

## 🔍 Vérification du fonctionnement

### Logs

Les logs d'envoi sont dans `backend/logs/app.log` :

```log
# Succès
[2026-01-09 11:20:15] ViteEtGourmand.INFO: Email de bienvenue envoyé avec succès {"email":"user@example.com","firstName":"John"}

# Config manquante
[2026-01-09 11:20:15] ViteEtGourmand.WARNING: Configuration SMTP manquante, email non envoyé {"email":"user@example.com"}

# Erreur SMTP
[2026-01-09 11:20:15] ViteEtGourmand.ERROR: Échec envoi email de bienvenue {"email":"user@example.com","error":"..."}
```

### LOG_FILE en production (Azure)

En production (notamment sur Azure App Service ou dans des conteneurs), il est fréquent que le répertoire `backend/logs` ne soit pas accessible en écriture ou que la plateforme collecte les logs via `stdout`/`stderr`.

- **Recommandation** : définir la variable d'environnement `LOG_FILE` sur `php://stderr` pour diriger les logs vers la sortie d'erreur standard, compatible avec les systèmes de logs d'Azure.
- **Alternative** : si vous préférez un fichier, assurez-vous que le répertoire existe et est inscriptible par le processus PHP (permissions et propriétaire). Evitez les espaces en début/fin de la variable (`LOG_FILE`) — un espace final peut provoquer une erreur comme `/home/LogFiles `.

Exemples :

```env
# utiliser stderr (recommandé pour Azure)
LOG_FILE=php://stderr

# ou, si vous créez un répertoire persisté et inscriptible
LOG_FILE=/home/LogFiles/app.log
```

Si `LOG_FILE` pointe vers un chemin non accessible, l'application basculera automatiquement vers `php://stderr` en production pour éviter que Monolog ne lance une erreur fatale et n'émette du HTML dans les réponses API.

### Tests API

Après inscription, vérifier la réponse JSON :

```json
{
  "success": true,
  "userId": 123,
  "emailSent": true,  // ✅ Email envoyé
  "message": "Inscription réussie et email de bienvenue envoyé."
}
```

Si `emailSent: false` :
```json
{
  "success": true,
  "userId": 123,
  "emailSent": false,  // ⚠️ Email non envoyé
  "message": "Inscription réussie, mais l'email de bienvenue n'a pas pu être envoyé."
}
```

## 🚨 Troubleshooting

### Erreur : "SMTP connect() failed"

**Causes possibles** :
- Mauvais host/port
- Firewall bloque le port 587
- Credentials invalides

**Solutions** :
1. Vérifier que port 587 est ouvert : `telnet smtp.example.com 587`
2. Tester credentials sur le site du provider
3. Vérifier les logs : `tail -f backend/logs/app.log`

### Erreur : "Could not authenticate"

**Solution** :
- Vérifier username/password
- Pour Gmail : utiliser un "Mot de passe d'application" (pas votre mot de passe principal)
- Pour SendGrid : username doit être exactement "apikey"

### Emails en spam

**Solutions** :
- Configurer SPF/DKIM sur votre domaine
- Utiliser un service professionnel (SendGrid, Mailgun)
- Ne pas utiliser @gmail.com en production

### Template non trouvé

**Erreur** : "Template email introuvable"

**Solution** :
```bash
# Vérifier que le fichier existe
ls -la backend/templates/emails/welcome.html

# Vérifier les permissions
chmod 644 backend/templates/emails/welcome.html
```

## 🔐 Sécurité

### Best practices

1. **Ne jamais committer les credentials SMTP** dans Git
2. Utiliser des variables d'environnement (`.env` dans `.gitignore`)
3. Utiliser des "App Passwords" (Gmail) ou API Keys (SendGrid)
4. Changer régulièrement les credentials en production
5. Échapper toutes les variables utilisateur dans les templates (déjà fait avec `htmlspecialchars()`)

### En production

- Utiliser HTTPS pour le site
- Activer SPF/DKIM/DMARC sur le domaine
- Monitorer les bounces et les plaintes spam
- Limiter le nombre d'emails envoyés par minute (rate limiting)

## 📊 Monitoring (production)

### SendGrid Dashboard

- Taux de livraison
- Taux d'ouverture
- Bounces / Spam complaints
- Analytics en temps réel

### Logs Monolog

Rotation automatique (7 jours) configurée dans `backend/config/container.php`

### Alertes

Configurer des alertes pour :
- Taux d'erreur SMTP > 5%
- Credentials expirés
- Quota dépassé

## 🧪 Tests et CI/CD

### Tests unitaires avec mock

Les tests utilisent `createMock(PHPMailer::class)` pour valider la logique d'envoi **sans connexion SMTP réelle** :

```bash
# Lancer les tests unitaires (rapides, pas de dépendances externes)
vendor/bin/phpunit tests/MailerServiceTest.php
```

**Avantages** :
- Rapides (< 100ms)
- Pas de dépendance externe
- Testent la logique métier (validation, template, logging)
- Fonctionnent toujours en CI/CD

### Tests d'intégration avec Mailtrap (CI/CD)

Pour tester l'envoi réel en GitHub Actions, configurez les **secrets GitHub** :

#### Étape 1 : Ajouter les secrets GitHub

1. Aller sur `Settings` → `Secrets and variables` → `Actions`
2. Cliquer sur **New repository secret**
3. Ajouter ces 4 secrets :

| Secret Name | Value | Description |
|-------------|-------|-------------|
| `MAIL_HOST` | `sandbox.smtp.mailtrap.io` | Serveur SMTP Mailtrap |
| `MAIL_USERNAME` | `votre_username` | Username Mailtrap |
| `MAIL_PASSWORD` | `votre_password` | Password Mailtrap |
| `MAIL_FROM_ADDRESS` | `noreply@vitegourmand.fr` | Adresse expéditeur |

#### Étape 2 : Utiliser les secrets dans le workflow

Les secrets sont injectés automatiquement dans `.env.test` via le workflow :

```yaml
# .github/workflows/email-integration.yml (exemple)
- name: Setup environment variables
  run: |
    echo "MAIL_HOST=${{ secrets.MAIL_HOST }}" >> .env.test
    echo "MAIL_USERNAME=${{ secrets.MAIL_USERNAME }}" >> .env.test
    echo "MAIL_PASSWORD=${{ secrets.MAIL_PASSWORD }}" >> .env.test
    echo "MAIL_FROM_ADDRESS=${{ secrets.MAIL_FROM_ADDRESS }}" >> .env.test
```

#### Étape 3 : Stratégie de test recommandée

**Option A (Recommended) : Tests manuels périodiques**
- Tests unitaires mock en CI/CD (à chaque commit)
- Tests d'intégration manuels avec Mailtrap (avant chaque release)
- Pas de secrets nécessaires en GitHub Actions

**Option B : Tests d'intégration automatiques**
- Ajouter les secrets GitHub Mailtrap
- Créer workflow spécifique `email-integration.yml`
- Lancer uniquement sur PR vers `main` ou quotidiennement (cron)

```yaml
# Exemple workflow quotidien
on:
  schedule:
    - cron: '0 9 * * *'  # Tous les jours à 9h
  workflow_dispatch:  # Lancement manuel
```

### Graceful degradation (valeur par défaut)

**Sans secrets GitHub** (configuration actuelle) :
- `.env.test` contient des placeholders
- `MailerService` détecte config manquante → log warning → retourne `false`
- L'inscription réussit avec `emailSent: false`
- Tous les tests API passent (ne vérifient pas `emailSent`)

✅ **Aucune action requise si vous acceptez que les emails ne soient pas envoyés en CI/CD**

## 📚 Ressources

- [PHPMailer GitHub](https://github.com/PHPMailer/PHPMailer)
- [SendGrid PHP Integration](https://docs.sendgrid.com/for-developers/sending-email/php)
- [Gmail SMTP Guide](https://support.google.com/mail/answer/7126229)
- [Mailtrap Documentation](https://mailtrap.io/email-sandbox/)

---

**Prochaines fonctionnalités mail** :
- Email de confirmation de commande
- Email de réinitialisation de mot de passe
- Email de notification admin (nouvelle commande)
- Templates multiples avec système de templating avancé
