#!/bin/bash
set -e
cd /var/www/html

if [ ! -f vendor/autoload.php ] && [ -f composer.json ]; then
  echo "Instalando dependencias PHP (composer install)..."
  composer install --no-dev --no-interaction --prefer-dist --ignore-platform-reqs
fi

exec apache2-foreground
