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

# Fallback para DATABASE_URL de Render si no está seteada en el entorno
if [ -z "$DATABASE_URL" ]; then
  DATABASE_URL="postgresql://docus_db_user:P6x1ka8UT36d2r8gMzhUDm9P9jhZvuIN@dpg-davsqejncjis73fko2o0-a/docus_db"
fi

if [ -n "$DATABASE_URL" ]; then
  export DB_CONNECTION=pgsql
  export DATABASE_URL="$DATABASE_URL"
  sed -i '/^DB_CONNECTION=/d' /var/www/html/.env 2>/dev/null || true
  sed -i '/^DATABASE_URL=/d' /var/www/html/.env 2>/dev/null || true
  sed -i '/^DB_HOST=/d' /var/www/html/.env 2>/dev/null || true
  sed -i '/^DB_PORT=/d' /var/www/html/.env 2>/dev/null || true
  sed -i '/^DB_DATABASE=/d' /var/www/html/.env 2>/dev/null || true
  sed -i '/^DB_USERNAME=/d' /var/www/html/.env 2>/dev/null || true
  sed -i '/^DB_PASSWORD=/d' /var/www/html/.env 2>/dev/null || true
  sed -i '/^DB_SSLMODE=/d' /var/www/html/.env 2>/dev/null || true

  echo "DB_CONNECTION=pgsql" >> /var/www/html/.env
  echo "DATABASE_URL=$DATABASE_URL" >> /var/www/html/.env
  echo "DB_SSLMODE=prefer" >> /var/www/html/.env
  echo "Configurada conexión a PostgreSQL con DATABASE_URL."
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

# Asegurar directorios y permisos de storage y cache
mkdir -p /var/www/html/storage/logs \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/framework/cache
touch /var/www/html/storage/logs/laravel.log
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Configurar puerto dinámico de Apache para Render (Render usa la variable $PORT, ej. 10000)
PORT="${PORT:-80}"
echo "Configurando Apache para escuchar en el puerto: $PORT"
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf || true
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$PORT>/g" /etc/apache2/sites-available/*.conf || true

# Limpiar cachés previas
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Ejecutar migraciones automáticamente
if [ "$RUN_MIGRATIONS" != "false" ]; then
  echo "Ejecutando migraciones de Laravel..."
  php artisan migrate --force || true

  echo "Ejecutando seeders de Laravel..."
  php artisan db:seed --class=SedesBoliviaSeeder --force || true
fi

# Asegurar permisos finales para www-data
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

echo "Iniciando servidor web..."
exec "$@"
