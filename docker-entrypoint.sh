#!/bin/bash
set -e

# Esperar a que la base de datos esté lista si es necesario
if [ -n "$DB_HOST" ]; then
  echo "Esperando conexión a base de datos en $DB_HOST:$DB_PORT..."
fi

# Generar APP_KEY si no está seteada
if [ -z "$APP_KEY" ]; then
  php artisan key:generate --force
fi

# Optimizar caché de Laravel
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Ejecutar migraciones automáticamente si RUN_MIGRATIONS=true o por defecto
if [ "$RUN_MIGRATIONS" != "false" ]; then
  echo "Ejecutando migraciones de Laravel..."
  php artisan migrate --force || true
  # Ejecutar seeders si es primera vez y se especifica RUN_SEEDERS=true
  if [ "$RUN_SEEDERS" = "true" ]; then
    echo "Ejecutando seeders iniciales..."
    php artisan db:seed --force || true
  fi
fi

# Iniciar Apache
exec "$@"
