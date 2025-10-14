# Utilise une image officielle PHP avec Apache
FROM php:8.2-apache

# Installer les dépendances nécessaires à PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copier ton code source dans le conteneur
COPY src/ /var/www/html/

# Définir le dossier de travail
WORKDIR /var/www/html

# Donner les bons droits
RUN chown -R www-data:www-data /var/www/html

# Activer mod_rewrite (optionnel mais utile)
RUN a2enmod rewrite

EXPOSE 80
