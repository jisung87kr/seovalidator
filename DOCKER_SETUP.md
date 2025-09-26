# Docker Development Environment Setup

This project includes a comprehensive Docker development environment with multi-service orchestration for Laravel 12.

## Services Overview

The Docker environment includes the following services:

- **web**: Nginx + PHP-FPM application server
- **mysql**: MySQL 8.0 database server
- **redis**: Redis 7.2 cache and session store
- **queue-worker**: Laravel queue worker processes
- **horizon**: Laravel Horizon queue management (optional)

## Prerequisites

- Docker Engine 20.10+
- Docker Compose 2.0+
- Git

## Quick Start

1. **Clone and enter the project directory:**
   ```bash
   git clone <repository-url>
   cd seovalidator
   ```

2. **Copy environment file:**
   ```bash
   cp .env.example .env
   ```

3. **Build and start services:**
   ```bash
   docker-compose up -d --build
   ```

4. **Install dependencies and setup Laravel:**
   ```bash
   docker-compose exec web composer install
   docker-compose exec web php artisan key:generate
   docker-compose exec web php artisan migrate
   ```

5. **Access the application:**
   - Application: http://localhost:8000
   - Database: localhost:3306
   - Redis: localhost:6379

## Service Configuration

### Web Service (Nginx + PHP-FPM)
- **Port**: 8000
- **PHP Version**: 8.2
- **Extensions**: MySQL, Redis, GD, Zip, Intl, BCMath
- **Features**: OPcache enabled, custom PHP configuration

### MySQL Service
- **Port**: 3306
- **Database**: seovalidator
- **Username**: laravel
- **Password**: laravel_password
- **Root Password**: root_password

### Redis Service
- **Port**: 6379
- **Memory Limit**: 256MB
- **Persistence**: RDB snapshots enabled
- **Eviction Policy**: allkeys-lru

## Directory Structure

```
.
├── docker-compose.yml              # Main orchestration file
├── Dockerfile                      # Application container definition
├── docker/                         # Service configurations
│   ├── nginx/
│   │   └── nginx.conf             # Nginx configuration
│   ├── php/
│   │   └── php.ini                # PHP configuration
│   ├── mysql/
│   │   └── my.cnf                 # MySQL configuration
│   ├── redis/
│   │   └── redis.conf             # Redis configuration
│   └── supervisord/
│       └── supervisord.conf       # Process management
└── .env.example                    # Environment template
```

## Development Workflow

### Starting Services
```bash
# Start all services
docker-compose up -d

# Start specific service
docker-compose up -d web mysql redis

# View logs
docker-compose logs -f web
```

### Laravel Commands
```bash
# Run artisan commands
docker-compose exec web php artisan migrate
docker-compose exec web php artisan queue:work

# Install packages
docker-compose exec web composer install
docker-compose exec web npm install
```

### Database Operations
```bash
# Connect to MySQL
docker-compose exec mysql mysql -u laravel -p seovalidator

# Run migrations
docker-compose exec web php artisan migrate

# Seed database
docker-compose exec web php artisan db:seed
```

### Queue Management

#### Standard Queue Worker
The `queue-worker` service automatically processes jobs from the Redis queue.

#### Laravel Horizon (Optional)
To enable Horizon for advanced queue management:

```bash
# Start Horizon service
docker-compose --profile horizon up -d horizon

# Access Horizon dashboard
# http://localhost:8000/horizon
```

## Troubleshooting

### Common Issues

1. **Port conflicts**: Change ports in docker-compose.yml if needed
2. **Permission issues**:
   ```bash
   docker-compose exec web chown -R www-data:www-data storage bootstrap/cache
   ```
3. **Cache issues**: Clear Laravel caches
   ```bash
   docker-compose exec web php artisan cache:clear
   docker-compose exec web php artisan config:clear
   ```

### Service Health Checks

```bash
# Check service status
docker-compose ps

# Test database connection
docker-compose exec web php artisan tinker
# >>> DB::connection()->getPdo()

# Test Redis connection
docker-compose exec web php artisan tinker
# >>> Redis::ping()
```

## Performance Tuning

### Production Optimizations

1. **Disable debug mode** in .env:
   ```
   APP_DEBUG=false
   ```

2. **Enable OPcache** (already configured in docker/php/php.ini)

3. **Use Redis for sessions and cache** (already configured)

4. **Adjust worker processes** in docker-compose.yml:
   ```yaml
   queue-worker:
     deploy:
       replicas: 4  # Increase based on load
   ```

## Security Considerations

- Database passwords should be changed in production
- Redis password should be set in production
- SSL certificates should be added for HTTPS
- Firewall rules should restrict database/Redis access
- Regular security updates for base images

## Monitoring

### Logs
```bash
# Application logs
docker-compose logs -f web

# Database logs
docker-compose logs -f mysql

# Queue worker logs
docker-compose logs -f queue-worker
```

### Health Checks
The configuration includes basic health monitoring. For production, consider:
- Container orchestration (Kubernetes, Docker Swarm)
- Application monitoring (New Relic, DataDog)
- Log aggregation (ELK stack, Fluentd)

## Backup and Recovery

### Database Backup
```bash
# Create backup
docker-compose exec mysql mysqldump -u laravel -p seovalidator > backup.sql

# Restore backup
docker-compose exec -T mysql mysql -u laravel -p seovalidator < backup.sql
```

### Volume Management
```bash
# List volumes
docker volume ls

# Backup volume
docker run --rm -v seovalidator_mysql_data:/source -v $(pwd):/backup alpine tar czf /backup/mysql_backup.tar.gz -C /source .
```

## Environment Variables

Key environment variables for Docker setup:

```bash
# Application
APP_NAME=SEOValidator
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=seovalidator
DB_USERNAME=laravel
DB_PASSWORD=laravel_password

# Redis
REDIS_HOST=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## Contributing

When modifying Docker configuration:

1. Test changes locally
2. Update documentation
3. Verify all services start correctly
4. Check service communication
5. Run application tests

## Support

For Docker-related issues:
- Check service logs: `docker-compose logs [service]`
- Verify configuration: `docker-compose config`
- Rebuild containers: `docker-compose up -d --build`
- Reset environment: `docker-compose down -v && docker-compose up -d --build`