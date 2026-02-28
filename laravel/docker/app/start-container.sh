#!/bin/bash

# Exit on error
set -e

echo "Starting container initialization..."

# Run migrations (safe to run on every start)
echo "Running migrations..."
php artisan migrate --force

# Initialize camera streams (wait for MediaMTX to be ready)
echo "Waiting for MediaMTX to be ready..."
sleep 10
echo "Initializing camera streams..."
php artisan cameras:initialize-streams

# Clear caches to ensure fresh config
echo "Clearing caches..."
php artisan config:clear
php artisan cache:clear

echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
