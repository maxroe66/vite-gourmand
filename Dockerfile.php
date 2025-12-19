# Base image PHP 8.0 FPM
FROM php:8.0-fpm

# Arguments pour l'utilisateur et le groupe
ARG UID=1000
ARG GID=1000

# Installer les dépendances système nécessaires pour les extensions PHP
# et les outils courants (git, curl, composer).
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libzip-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP requises pour le projet
RUN docker-php-ext-install pdo pdo_mysql zip mbstring

# Installer le driver MongoDB
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Installer Composer (gestionnaire de dépendances PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Créer un utilisateur non-root pour améliorer la sécurité
RUN groupadd -g $GID -o vite_group && \
    useradd -m -u $UID -g vite_group -o -s /bin/bash vite_user

# Changer d'utilisateur
USER vite_user

# Définir le répertoire de travail
WORKDIR /var/www/vite_gourmand

# Copier uniquement les fichiers de dépendances et installer les vendors
# Cela permet de bénéficier du cache Docker si les dépendances ne changent pas
COPY --chown=vite_user:vite_group composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader

# Copier le reste des fichiers de l'application
COPY --chown=vite_user:vite_group . .

# Générer l'autoloader de Composer
RUN composer dump-autoload --optimize

# Exposer le port 9000 sur lequel PHP-FPM écoute
EXPOSE 9000

# Commande par défaut pour démarrer le service PHP-FPM
CMD ["php-fpm"]
