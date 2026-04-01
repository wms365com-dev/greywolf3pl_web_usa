#!/bin/sh
set -eu

PORT="${PORT:-8080}"
export PORT

if [ -f /etc/apache2/sites-available/000-default-template.conf ]; then
  envsubst '${PORT}' < /etc/apache2/sites-available/000-default-template.conf > /etc/apache2/sites-available/000-default.conf
fi

sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf

STORAGE_DIR="${GW_STORAGE_DIR:-/var/www/html/form_submissions}"
mkdir -p "${STORAGE_DIR}"
chown -R www-data:www-data "${STORAGE_DIR}" || true

exec apache2-foreground
