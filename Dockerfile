FROM php:7.4-apache

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    netcat \
    ca-certificates \
    && update-ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Installation des extensions PHP
RUN docker-php-ext-install \
    pdo_mysql \
    mysqli \
    zip

# Configuration d'Apache
RUN a2enmod rewrite
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/apache2.conf

# Installation de Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers composer AVANT l'installation
COPY composer.json composer.lock* ./

# Installer les dépendances Composer
RUN composer install --optimize-autoloader --no-interaction

# Copier le reste du code
COPY . .


# Créer le dossier pour les uploads avec les bonnes permissions
RUN mkdir -p public/storage \
    && chown -R www-data:www-data public/storage \
    && chmod -R 755 public/storage

# Vérifier que l'autoloader existe et générer le cache
RUN composer dump-autoload --optimize

# S'assurer que les permissions sont correctes
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Point d'entrée simplifié
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["apache2-foreground"]