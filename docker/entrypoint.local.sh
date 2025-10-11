#!/bin/bash

if [ ! -f "vendor/autoload.php" ]; then
    composer install --no-progress --no-interaction
fi

if [ ! -f ".env" ]; then
    echo "Creating env file for env $APP_ENV"
    cp .env.example .env
else
    echo "env file exists."
fi

php artisan migrate
php artisan optimize clear
php artisan view:clear
php artisan route:clear

php-fpm &
nginx -g "daemon off;" &

while true; do
    echo "Running Laravel scheduler at $(date)" >&2
    php /app/artisan schedule:run --verbose --no-interaction 2>&1
    sleep 60
done
