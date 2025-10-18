# -----------------------------
# Stage 1: Build PHP dependencies
# -----------------------------
    FROM composer:2.6 AS build
    WORKDIR /var/www/html
    
    # Copy entire Laravel project
    COPY . .
    
    # Install PHP dependencies
    RUN composer install --optimize-autoloader
    
    # -----------------------------
    # Stage 2: Production container
    # -----------------------------
    FROM php:8.2-apache
    WORKDIR /var/www/html
    
    # Install PHP extensions required by Laravel
    RUN docker-php-ext-install pdo pdo_mysql bcmath
    
    # Enable Apache mod_rewrite (for Laravel routes)
    RUN a2enmod rewrite
    
    # Copy application from build stage
    COPY --from=build /var/www/html /var/www/html
    
    # Set correct permissions for storage & cache
    RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
    
    # Expose port Render expects
    EXPOSE 10000
    
    CMD php artisan migrate:fresh --seed --force && php artisan serve --host=0.0.0.0 --port=10000