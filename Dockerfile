# -----------------------------
# Stage 1: Build PHP dependencies
# -----------------------------
    FROM composer:2.6 AS build
    WORKDIR /var/www/html
    
    # Copy Laravel app
    COPY . .
    
    # Install PHP dependencies
    RUN composer install --optimize-autoloader
    
    # -----------------------------
    # Stage 2: Production container
    # -----------------------------
    FROM php:8.2-apache
    WORKDIR /var/www/html
    
    # Install system dependencies and PHP extensions
    RUN apt-get update && apt-get install -y \
        libonig-dev \
        libzip-dev \
        libxml2-dev \
        libcurl4-openssl-dev \
        zip \
        unzip \
        git \
        && docker-php-ext-install pdo pdo_mysql bcmath mbstring xml curl zip \
        && apt-get clean && rm -rf /var/lib/apt/lists/*
    
    # Enable Apache mod_rewrite for Laravel routes
    RUN a2enmod rewrite
    
    # Set Apache DocumentRoot to Laravel public folder
    RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
    
    # Copy Laravel app from build stage
    COPY --from=build /var/www/html /var/www/html
    
    # Set permissions for storage, cache, and vendor
    RUN chown -R www-data:www-data storage bootstrap/cache vendor
    RUN chmod -R 775 storage bootstrap/cache vendor
    
    # Expose Apache port
    EXPOSE 80
    
    # Start Apache
    CMD ["apache2-foreground"]
    