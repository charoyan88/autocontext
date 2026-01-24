#!/bin/bash

echo "🚀 Starting Auto-Context project..."

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker first."
    exit 1
fi

# Stop existing containers if running
echo "🛑 Stopping existing containers..."
docker-compose down

# Build and start containers
echo "🔨 Building and starting containers..."
docker-compose up -d --build

# Wait for database to be ready
echo "⏳ Waiting for database to be ready..."
sleep 5

# Run migrations
echo "📊 Running database migrations..."
docker exec autocontext-app php artisan migrate --force

# Seed demo data
echo "🌱 Seeding demo data..."
docker exec autocontext-app php artisan demo:seed

# Clear caches
echo "🧹 Clearing caches..."
docker exec autocontext-app php artisan cache:clear
docker exec autocontext-app php artisan view:clear
docker exec autocontext-app php artisan config:clear

# Show status
echo ""
echo "✅ Project started successfully!"
echo ""
echo "📋 Container status:"
docker ps --filter "name=autocontext" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo ""
echo "🌐 Access the application:"
echo "   - Frontend: http://localhost:8080"
echo "   - Vite Dev Server: http://localhost:5173"
echo ""
echo "👤 Demo credentials:"
echo "   - Email: admin@example.com"
echo "   - Password: password"

echo ""
echo "📝 Useful commands:"
echo "   - View logs: docker-compose logs -f"
echo "   - Stop project: docker-compose down"
echo "   - Restart project: docker-compose restart"
