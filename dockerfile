# Basis-Image mit PHP und Apache
FROM php:8.2-apache

# Installiere alle dedalo-relevanten PHP-Extensions und Systemtools
RUN apt-get update && apt-get install -y \
    libpq-dev libxml2-dev libgd-dev ffmpeg imagemagick poppler-utils \
    && docker-php-ext-install pdo pdo_pgsql gd mbstring xml soap bcmath zip

# Aktiviere typische Apache-Module
RUN a2enmod rewrite headers

# Quellcode ins Webroot kopieren
COPY . /var/www/html/

# Berechtigungen setzen
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
