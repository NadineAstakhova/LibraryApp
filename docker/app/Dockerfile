FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    wget \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    default-mysql-client \
    nodejs \
    npm \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy php.ini customizations
COPY ./docker/app/php.ini /usr/local/etc/php/conf.d/custom.ini

# Create startup script
RUN printf '#!/bin/bash\n\
if [ ! -f /var/www/html/vendor/autoload.php ]; then\n\
    echo "Installing composer dependencies..."\n\
    composer install --no-interaction --prefer-dist --optimize-autoloader\n\
fi\n\
if [ ! -f /var/www/html/.env ]; then\n\
    echo "Creating .env file..."\n\
    cp .env.example .env\n\
    php artisan key:generate\n\
    php artisan jwt:secret --force\n\
fi\n\
php artisan l5-swagger:generate\n\
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache\n\
exec php-fpm\n' > /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

EXPOSE 9000
CMD ["/usr/local/bin/start.sh"]