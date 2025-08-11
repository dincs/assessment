FROM php:8.2-apache

# Install system libs for PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev zip \
    libpng-dev libjpeg-dev libfreetype6-dev \
 && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite
RUN a2enmod rewrite

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_mysql zip gd

# Set Laravel public as DocumentRoot
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy application code
COPY . /var/www/html

# Copy env file from host into container and rename
COPY .env.docker /var/www/html/.env

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP dependencies
WORKDIR /var/www/html
RUN composer install --no-interaction --prefer-dist

# Set permissions for Laravel
RUN chown -R www-data:www-data storage bootstrap/cache
