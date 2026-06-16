FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
        libzip-dev libcurl4-openssl-dev libxml2-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring curl xml zip bcmath intl \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
