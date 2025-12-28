#!/bin/sh
set -e

# Default behaviour: set permissions and run optional artisan optimizations
if [ -f artisan ]; then
  echo "Setting storage permissions..."
  chown -R www-data:www-data storage bootstrap/cache || true
  chmod -R ug+rwx storage bootstrap/cache || true

  # If APP_KEY missing and running first time, attempt to generate
  if [ -z "${APP_KEY}" ] || [ "${APP_KEY}" = "" ]; then
    echo "No APP_KEY set. Skipping key generation in container; generate one externally."
  fi

  if [ "$APP_ENV" = "production" ]; then
    echo "Running artisan optimize..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
  fi
fi

exec "$@"
