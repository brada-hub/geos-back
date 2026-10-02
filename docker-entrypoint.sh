#!/bin/bash
set -e

# Asegurar que el archivo .env exista para comandos de Artisan
if [ ! -f /var/www/html/.env ]; then
  if [ -f /var/www/html/.env.example ]; then
    cp /var/www/html/.env.example /var/www/html/.env
  else
    touch /var/www/html/.env
  fi
fi

# Si APP_KEY viene en las variables de entorno de Render, escribirla en .env si está vacía
if [ -n "$APP_KEY" ]; then
  if ! grep -q "^APP_KEY=" /var/www/html/.env 2>/dev/null; then
    echo "APP_KEY=$APP_KEY" >> /var/www/html/.env
  fi
else
  # Si no existe APP_KEY, generarla
  php artisan key:generate --force || true
fi

# Configurar puerto dinámico de Apache para Render (Render usa la variable $PORT, ej. 10000)
PORT="${PORT:-80}"
echo "Configurando Apache para escuchar en el puerto: $PORT"
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf || true
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$PORT>/g" /etc/apache2/sites-available/*.conf || true

# Optimizar caché de Laravel
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Ejecutar migraciones automáticamente
if [ "$RUN_MIGRATIONS" != "false" ]; then
  echo "Ejecutando migraciones de Laravel..."
  php artisan migrate --force || true

  if [ "$RUN_SEEDERS" = "true" ]; then
    echo "Ejecutando seeders iniciales..."
    php artisan db:seed --force || true
  fi
fi

echo "Iniciando servidor web..."
exec "$@"
