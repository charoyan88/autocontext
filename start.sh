#!/bin/bash

set -euo pipefail

echo "Starting Auto-Context project..."

if ! docker info > /dev/null 2>&1; then
    echo "Docker is not running. Start Docker first."
    exit 1
fi

echo "Stopping existing containers..."
docker compose down

echo "Building and starting containers..."
docker compose up -d --build

echo "Waiting for database to be ready..."
sleep 5

echo "Installing PHP dependencies..."
docker compose exec app composer install

echo "Installing frontend dependencies..."
docker compose exec vite npm install
docker compose exec vite npm run build

echo "Running database migrations..."
docker compose exec app php artisan migrate --force

echo "Seeding demo data..."
docker compose exec app php artisan demo:seed

echo "Clearing caches..."
docker compose exec app php artisan cache:clear
docker compose exec app php artisan view:clear
docker compose exec app php artisan config:clear

echo
echo "Project started successfully."
echo
echo "Container status:"
docker ps --filter "name=autocontext" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo
echo "Access the application:"
echo "  - Dashboard: http://localhost:8080"
echo "  - Vite Dev Server: http://localhost:5173"
echo
echo "Demo credentials:"
echo "  - Email: admin@auto-context.local"
echo "  - Password: password"
echo
echo "Useful commands:"
echo "  - View logs: docker compose logs -f"
echo "  - Stop project: docker compose down"
echo "  - Restart project: docker compose restart"
