#!/bin/bash
set -e

# Copy nginx config
cp /var/www/docker/nginx.conf /etc/nginx/sites-available/default
ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Generate app key
php /var/www/artisan key:generate --no-interaction

# Link storage
php /var/www/artisan storage:link

# Start PHP-FPM
php-fpm &

# Start Nginx
nginx -g 'daemon off;'