FROM php:8.3-fpm-alpine

# Install system deps
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pcntl bcmath gd exif

# Install Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Node.js for assets
RUN apk add --no-cache nodejs npm

WORKDIR /var/www

# Copy full app (including vendor if present in build context)
COPY . .

# Only run composer install if vendor is missing (fresh clone)
# In normal dev CI/CD the vendor folder is already mounted
RUN if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then \
      composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev; \
    fi

# Permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]