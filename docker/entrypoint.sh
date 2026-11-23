#!/bin/sh
set -eu

mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs

if [ -z "${APP_KEY:-}" ]; then
    if [ ! -s storage/app.key ]; then
        php -r "echo 'base64:' . base64_encode(random_bytes(32));" > storage/app.key
    fi

    APP_KEY="$(cat storage/app.key)"
    export APP_KEY
fi

printf 'APP_KEY=%s\n' "$APP_KEY" > .env

php artisan migrate --force --no-interaction

exec php artisan serve --host=0.0.0.0 --port=8000
