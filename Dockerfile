FROM php:7.4-fpm
RUN apt-get update && apt-get install -y libzip-dev zip && docker-php-ext-install pdo_mysql zip
WORKDIR /var/www/html
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html
