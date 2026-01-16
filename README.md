# Auto-Context MVP

**Smart log filtering and context enrichment service for deployment-aware observability.**

Auto-Context automatically enriches your logs with deployment context, filters out noise, and aggregates errors—saving you money on log storage and processing costs.

## 🎯 Features

- **🔑 API Key Authentication** - Secure multi-tenant access
- **📊 Deployment Tracking** - Link logs to specific deployments
- **🎨 Context Enrichment** - Auto-attach deployment metadata to logs
- **🔇 Smart Filtering** - Remove DEBUG/TRACE logs and health-check noise
- **📈 Statistics Tracking** - Real-time metrics with Redis + PostgreSQL
- **🚨 Error Aggregation** - Deduplicate and track error patterns
- **📤 Log Forwarding** - Send filtered logs to HTTP or file destinations
- **📊 Dashboard** - Beautiful web UI with Chart.js visualizations
- **💰 Cost Savings** - Track how much you're saving on log processing

## 🏗️ Architecture

- **Backend**: Laravel 12 (PHP 8.3)
- **Database**: PostgreSQL 16
- **Cache/Queue**: Redis 7
- **Web Server**: Nginx
- **Frontend**: Blade + Tailwind CSS + Chart.js
- **Deployment**: Docker Compose

## 🚀 Quick Start

### Prerequisites

- Docker & Docker Compose
- Git

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/charoyan88/autocontext.git
cd autocontext
```

2. **Start Docker containers**
```bash
docker compose up -d
```

3. **Install dependencies**
```bash
docker compose exec app composer install
```

4. **Generate application key**
```bash
docker compose exec app php artisan key:generate
```

5. **Run migrations and seed demo data**
```bash
docker compose exec app php artisan demo:seed
```

6. **Access the application**
- **Dashboard**: http://localhost:8080/login
  - Email: `admin@auto-context.local`
  - Password: `password`
- **API**: http://localhost:8080/api

## 📡 API Usage

### Authentication

All API requests require an `X-Api-Key` header. Get your API key from the dashboard or use the demo key from seeder output.

### Send Logs

**Single log:**
```bash
curl -X POST http://localhost:8080/api/logs \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "timestamp": "2025-12-10T12:00:00+00:00",
    "level": "ERROR",
    "message": "Database connection failed",
    "context": {
      "exception": "PDOException",
      "file": "Database.php",
      "line": 42
    }
  }'
```

**Batch logs:**
```bash
curl -X POST http://localhost:8080/api/logs \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "events": [
      {"timestamp": "2025-12-10T12:00:00+00:00", "level": "INFO", "message": "Request started"},
      {"timestamp": "2025-12-10T12:00:01+00:00", "level": "ERROR", "message": "Request failed"}
    ]
  }'
```

### Track Deployments

```bash
curl -X POST http://localhost:8080/api/deployments \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "version": "v1.2.0",
    "environment": "production",
    "region": "us-east-1",
    "started_at": "2025-12-10T12:00:00+00:00"
  }'
```

## 🔧 Configuration

### Environment Variables

Key variables in `.env`:

```env
# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_DATABASE=auto_context
DB_USERNAME=auto
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# Application
APP_URL=http://localhost:8080
```

### Log Filtering Rules

Edit `config/log_filter.php` to customize:

```php
return [
    'noise_levels' => ['DEBUG', 'TRACE'],
    'health_check_paths' => ['/health', '/ping', '/metrics'],
    'duplicate_error_threshold' => 10, // errors per minute
];
```

## 📊 Dashboard Features

- **Projects Overview** - All projects with stats
- **Project Dashboard** - Real-time metrics with Chart.js
- **API Keys Management** - Generate and manage keys
- **Downstream Configuration** - UI for converting logs to File or HTTP destinations
- **Downstream Configuration** - UI for converting logs to File or HTTP destinations
- **Deployments Timeline** - Track deployment history
- **Error Aggregation** - View top errors with counts
- **Savings Calculator** - See cost reduction percentage

## 🧪 Running Tests

```bash
# Run all tests
docker compose exec app php artisan test

# Run specific test suite
docker compose exec app php artisan test --testsuite=Feature
```

## 📈 Scheduler

The hourly stats flush job runs automatically. To start the scheduler:

```bash
docker compose exec app php artisan schedule:work
```

Or configure a cron job:
```cron
* * * * * cd /path/to/autocontext && docker compose exec -T app php artisan schedule:run >> /dev/null 2>&1
```

## 🐛 Troubleshooting

### Queue worker not processing jobs

```bash
docker compose restart queue-worker
docker compose logs queue-worker
```

### Database connection issues

```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
```

### View logs

```bash
docker compose logs app
docker compose logs queue-worker
docker compose exec app tail -f storage/logs/laravel.log
```

## 🏗️ Development

### Project Structure

```
app/
├── Http/Controllers/
│   ├── Api/              # API endpoints
│   └── Web/              # Dashboard controllers
├── Jobs/                 # Queue jobs
├── Models/               # Eloquent models
└── Services/             # Business logic
    ├── LogContextEnricher.php
    ├── LogFilter.php
    ├── LogForwarder.php
    ├── StatsRecorder.php
    └── ErrorAggregator.php
```

### Adding a New Downstream

1. Add type to `downstream_endpoints` migration
2. Implement forwarding logic in `LogForwarder::forward()`
3. Update dashboard UI

## 📝 License

MIT License - see LICENSE file for details

## 🤝 Contributing

Contributions welcome! Please open an issue or PR.

## 📧 Contact

- GitHub: [@charoyan88](https://github.com/charoyan88)
- Repository: [autocontext](https://github.com/charoyan88/autocontext)

---

**Built with ❤️ using Laravel**
