FROM php:8.2-apache

# Habilita mod_rewrite (se precisar) e instala o driver pdo_mysql
RUN a2enmod rewrite \
    && docker-php-ext-install pdo_mysql mysqli

