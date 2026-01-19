#!/bin/sh
set -eu

docker compose up -d --build

docker compose exec app composer install
docker compose exec vite npm install
docker compose exec vite npm run build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan demo:seed

echo "Auto-Context is up. App: http://localhost:8080"
