# -----------------------------
# Stage 1: Build Composer & Assets
# -----------------------------
    FROM composer:2.6 AS build
    WORKDIR /var/www/html
    
    # Copy entire Laravel project
    COPY . .
    
    # Install PHP dependencies
    RUN composer install --no-dev --optimize-autoloader
    
    # If you have frontend assets (optional)
    # Comment these out if you only use Laravel as an API
    RUN apt-get update && apt-get install -y npm
    RUN npm install && npm run build
    
    # -----------------------------
    # Stage 2: Production container
    # -----------------------------
    FROM php:8.2-apache
    WORKDIR /var/www/html
    
    # Install PHP extensions required by Laravel
    RUN docker-php-ext-install pdo pdo_mysql bcmath
    
    # Enable Apache mod_rewrite (for pretty URLs)
    RUN a2enmod rewrite
    
    # Copy app from build stage
    COPY --from=build /var/www/html /var/www/html
    
    # Set correct permissions for Laravel storage and cache
    RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
    
    # Expose the port Render uses
    EXPOSE 10000
    
    # Laravel’s built-in server (simpler for Render)
    CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=10000"]
    