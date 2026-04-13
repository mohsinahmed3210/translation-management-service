# Translation Management Service

A high-performance, API-driven translation management service built with Laravel 11. Supports multiple locales, tag-based context, token authentication, and a JSON export endpoint optimised for frontend consumption.

---

## Features

- Multi-locale support (`en`, `fr`, `es`, …) — add new languages without schema changes
- Tag-based context (`mobile`, `desktop`, `web`, …)
- Full CRUD API for translations
- Search by key, content, locale, group, or tags
- JSON export endpoint with Redis caching (cache-busted on every write)
- Token-based authentication via Laravel Sanctum
- OpenAPI / Swagger documentation at `/api/documentation`
- Artisan command to seed 100k+ records for scalability testing
- Docker setup (PHP-FPM + Nginx + MySQL + Redis)
- 50 automated tests (unit + feature)

---

## Quick Start (Docker)

```bash
# 1. Clone and enter the project
git clone <repo-url> translation-management-service
cd translation-management-service

# 2. Copy environment file
cp .env.example .env

# 3. Start all services
docker-compose up -d

# 4. Run migrations and seed base data
docker-compose exec app php artisan migrate --seed

# 5. (Optional) Seed 100k translations for performance testing
docker-compose exec app php artisan translations:seed --count=100000

# 6. Generate Swagger docs
docker-compose exec app php artisan l5-swagger:generate

# API is now available at http://localhost:8000
# Swagger UI at http://localhost:8000/api/documentation
```

---

## Local Setup (without Docker)

**Requirements:** PHP 8.2, Composer, MySQL 8+, Redis

```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
# Edit .env: set DB_* and REDIS_* values

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate --seed

# Seed large dataset (optional)
php artisan translations:seed --count=100000

# Start dev server
php artisan serve
```

---

## Running Tests

```bash
# All tests
php artisan test

# With coverage (requires Xdebug or PCOV)
php artisan test --coverage

# Only feature tests
php artisan test --testsuite=Feature

# Only unit tests
php artisan test --testsuite=Unit
```

> **Note:** Tests use a separate `translation_service_test` database. Make sure it exists and the `DB_*` credentials in `phpunit.xml` match your setup.

---

## API Endpoints

All endpoints (except auth) require `Authorization: Bearer <token>`.

### Auth

| Method | Endpoint              | Description              |
|--------|-----------------------|--------------------------|
| POST   | `/api/auth/register`  | Register, receive token  |
| POST   | `/api/auth/login`     | Login, receive token     |
| POST   | `/api/auth/logout`    | Revoke current token     |
| GET    | `/api/auth/me`        | Get authenticated user   |

### Translations

| Method | Endpoint                     | Description                        |
|--------|------------------------------|------------------------------------|
| GET    | `/api/translations`          | List / search (paginated)          |
| POST   | `/api/translations`          | Create a translation               |
| GET    | `/api/translations/{id}`     | Get a single translation           |
| PUT    | `/api/translations/{id}`     | Update a translation               |
| DELETE | `/api/translations/{id}`     | Delete a translation               |
| GET    | `/api/export/{locale}`       | JSON export for Vue.js / i18n      |

### Search Parameters (`GET /api/translations`)

| Parameter  | Example              | Description                        |
|------------|----------------------|------------------------------------|
| `locale`   | `en`                 | Filter by locale                   |
| `tags`     | `web,mobile`         | Comma-separated tag names          |
| `key`      | `btn.save`           | Partial key match                  |
| `search`   | `Welcome`            | Search in key and value            |
| `group`    | `auth`               | Filter by group                    |
| `per_page` | `50`                 | Items per page (max 100)           |

---

## Seeding Large Datasets

```bash
# Default: 100,000 records
php artisan translations:seed

# Custom count with custom chunk size
php artisan translations:seed --count=200000 --chunk=2000
```

The command uses bulk `INSERT` statements in configurable chunks — no Eloquent overhead per row. On a standard dev machine, 100k records seed in under 60 seconds.

---

## Design Decisions

### Repository Pattern
Controllers depend on interfaces, not concrete classes. The `AppServiceProvider` binds the concrete implementations. This makes swapping data sources (e.g. for testing) trivial and keeps each class focused on one concern.

### Service Layer
Business logic (cache invalidation, tag resolution) lives in `TranslationService` and `AuthService`, not in controllers. Controllers translate HTTP requests to service calls and format responses — nothing more.

### Caching Strategy
The export endpoint is the most performance-sensitive path. Results are stored in Redis with a 1-hour TTL. Any write operation (create, update, delete) immediately busts the cache for the affected locale, so the endpoint always returns fresh data without a full database hit on every request.

### Database Schema
- Composite unique index on `(locale, key)` prevents duplicate translation keys.
- FULLTEXT index on `(key, value)` enables fast search across large datasets (MySQL only; LIKE fallback used in tests via SQLite/MySQL without FULLTEXT).
- A separate `tags` table with a pivot table keeps the schema normalised and makes tag-based lookups efficient.

### No External CRUD Libraries
All data access is done with Laravel's built-in Eloquent and Query Builder. No third-party CRUD packages are used, per the requirements.

### Token Authentication
Laravel Sanctum issues opaque Bearer tokens. On each login, previous tokens are revoked so only one active token exists per user at a time.

---

## Project Structure

```
app/
├── Console/Commands/        # SeedTranslationsCommand (100k+ seeder)
├── Http/
│   ├── Controllers/         # AuthController, TranslationController, ExportController
│   ├── Requests/            # Form request validation classes
│   └── Resources/           # API resource transformers
├── Models/                  # Translation, Tag, User
├── Providers/               # AppServiceProvider (DI bindings)
├── Repositories/
│   ├── Contracts/           # Interfaces
│   └── TranslationRepository, TagRepository
└── Services/                # TranslationService, AuthService

database/
├── factories/               # TranslationFactory, TagFactory
├── migrations/
└── seeders/                 # DatabaseSeeder, TagSeeder

tests/
├── Unit/                    # AuthServiceTest, TagRepositoryTest, TranslationServiceTest
└── Feature/                 # AuthTest, TranslationCrudTest, ExportTest

docker/
├── nginx/default.conf
├── php/opcache.ini, php.ini
└── supervisor/supervisord.conf
```

---

## Environment Variables

| Variable       | Default              | Description                     |
|----------------|----------------------|---------------------------------|
| `DB_CONNECTION`| `mysql`              | Database driver                 |
| `DB_DATABASE`  | `translation_service`| Database name                   |
| `CACHE_STORE`  | `redis`              | Cache driver (redis recommended)|
| `REDIS_HOST`   | `127.0.0.1`          | Redis host                      |
| `CDN_URL`      | _(empty)_            | Optional CDN prefix for assets  |
