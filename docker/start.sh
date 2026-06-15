#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p /var/www/html/uploads/avatars /var/www/html/logs /var/www/html/storage
chown -R www-data:www-data /var/www/html/uploads /var/www/html/logs /var/www/html/storage
chmod -R ug+rwX /var/www/html/uploads /var/www/html/logs /var/www/html/storage

if [ ! -d vendor ]; then
  composer install --no-dev --optimize-autoloader --no-interaction
fi

AUTO_DB_INSTALL="${AUTO_DB_INSTALL:-true}"

if [ "${AUTO_DB_INSTALL}" = "true" ]; then
  echo "Waiting for database connection..."
  i=0
  until php shell.php db:status >/dev/null 2>&1; do
    i=$((i + 1))
    if [ "${i}" -ge 60 ]; then
      echo "Database is not reachable after 120 seconds."
      exit 1
    fi
    sleep 2
  done

  echo "Running database install..."
  php shell.php db:install
fi

exec apache2-foreground
