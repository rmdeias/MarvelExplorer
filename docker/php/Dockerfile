FROM php:8.3-fpm

# Installer les dépendances système et PHP nécessaires
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libpq-dev libzip-dev zip curl libxml2-dev bash \
    && docker-php-ext-install intl pdo pdo_mysql zip opcache \
    && pecl install apcu \
    && docker-php-ext-enable apcu

# Installer Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Installer la CLI Symfony
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony \
    && chmod +x /usr/local/bin/symfony


# Vérification
RUN symfony -v || echo "Symfony CLI installed."

# Créer le dossier de travail
WORKDIR /var/www/html

# Copier les fichiers de dépendances
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-interaction --no-progress --prefer-dist

# Copier le reste du code
COPY . .

# Fixer les permissions
RUN mkdir -p var && chown -R www-data:www-data var

EXPOSE 9000
CMD ["php-fpm"]
