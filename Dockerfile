FROM php:8.2-apache-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libicu-dev libzip-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl pdo_mysql zip opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite headers

WORKDIR /var/www/html
