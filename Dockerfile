FROM php:8.1-apache

# Installa estensioni PHP necessarie
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libicu-dev \
    zip \
    curl \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql zip exif pcntl intl

# Abilita il modulo rewrite di Apache
RUN a2enmod rewrite headers

# Imposta la directory di lavoro
WORKDIR /var/www/html

# Copia i file di progetto
COPY . /var/www/html/

# Crea le directory cache e imposta i permessi
RUN mkdir -p /var/www/html/server/cache/data \
    && mkdir -p /var/www/html/server/cache/ratelimit \
    && chmod -R 755 /var/www/html/server/cache \
    && chown -R www-data:www-data /var/www/html/server/cache

# Configura Apache
COPY docker/site.conf /etc/apache2/sites-available/000-default.conf

# Espone la porta
EXPOSE 80

# Avvia Apache in foreground
CMD ["apache2-foreground"]