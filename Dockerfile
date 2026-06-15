FROM php:8.3-apache

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        default-mysql-client \
        unzip \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY composer.json composer.lock /var/www/html/

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html \
    && chmod +x /var/www/html/docker/start.sh

ENTRYPOINT ["/var/www/html/docker/start.sh"]
