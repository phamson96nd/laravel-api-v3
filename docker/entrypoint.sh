#!/bin/bash

DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-}
DB_DATABASE=${DB_DATABASE:-mydb}

echo "Creating database ${DB_DATABASE} if it doesn't exist..."
mysql -h $DB_HOST -P $DB_PORT -u $DB_USERNAME -p$DB_PASSWORD \
      -e "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"


if [ ! -f "vendor/autoload.php" ]; then
    composer install --no-ansi --no-dev --no-interaction --no-plugins --no-progress --no-scripts --optimize-autoloader
fi

if [ ! -f ".env" ]; then
    echo "Creating env file for env $APP_ENV"
    cp .env.example .env
    case "$APP_ENV" in
    "local")
        echo "Copying .env.example ... "
        cp .env.example .env
    ;;
    "prod")
        echo "Copying .env.prod ... "
        cp .env.prod .env
    ;;
    esac
else
    echo "env file exists."
fi

# php artisan migrate
php artisan clear
php artisan optimize:clear
php artisan migrate --force


# Fix files ownership.
chown -R www-data .
chown -R www-data /app/storage
chown -R www-data /app/storage/logs
chown -R www-data /app/storage/framework
chown -R www-data /app/storage/framework/sessions
chown -R www-data /app/bootstrap
chown -R www-data /app/bootstrap/cache

# Set correct permission.
chmod -R 775 /app/storage
chmod -R 775 /app/storage/logs
chmod -R 775 /app/storage/framework
chmod -R 775 /app/storage/framework/sessions
chmod -R 775 /app/bootstrap
chmod -R 775 /app/bootstrap/cache

php-fpm -D
nginx -g "daemon off;"
