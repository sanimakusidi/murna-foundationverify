FROM composer:2 AS composer

FROM dunglas/frankenphp:php8.4.22-bookworm

# Copy Composer from the official Composer image
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Install mysqli and pdo_mysql extensions
RUN docker-php-ext-install mysqli pdo_mysql

# Set working directory
WORKDIR /app

# Copy application source
COPY . .

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose FrankenPHP default port
EXPOSE 8080

CMD ["frankenphp", "php-server"]
