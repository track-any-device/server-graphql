#!/bin/sh
set -e

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set — generating a temporary key for this container instance."
    APP_KEY="base64:$(openssl rand -base64 32)"
    export APP_KEY
fi

php artisan optimize:clear
php artisan package:discover --ansi

exec "$@"
