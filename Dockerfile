FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev zip git unzip && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd pdo pdo_mysql bcmath

COPY . /var/www/html/

WORKDIR /var/www/html

RUN composer install --no-dev --optimize-autoloader && \
    php artisan key:generate && \
    php artisan migrate --seed && \
    npm install && \
    npm run prod

EXPOSE 80
CMD ["apache2-foreground"]