#!/bin/bash

# Start PHP-FPM in background
php-fpm &

# Start Redis queue worker
php artisan queue:work --sleep=3 --tries=3 &

# Start Laravel server
php artisan serve --host=0.0.0.0 --port=8000
