FROM php:8.2-cli

WORKDIR /app

# Install extensions and tools
RUN apt-get update && apt-get install -y git unzip zlib1g-dev libzip-dev mariadb-client \
    && docker-php-ext-install pdo pdo_mysql zip

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy app
COPY . /app

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist || true

EXPOSE 8000

# Ensure log dir exists
RUN mkdir -p /var/log/app && chown -R www-data:www-data /var/log/app || true

CMD ["php","-S","0.0.0.0:8000","-t","public"]
