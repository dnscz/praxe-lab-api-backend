# Backend for LAB  – REST API

API-only Laravel 13 kit following the 2025-2026 REST API ecosystem best practices.

[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-13.x-red)](https://laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)
[![tests](https://github.com/dnscz/praxe-lab-api-backend/actions/workflows/tests.yml/badge.svg)](https://github.com/dnscz/praxe-lab-api-backend/actions/workflows/tests.yml)

## Requirements

- Docker & Docker Compose
- Or: PHP 8.3+, Composer 2.x

## Quick Start

### With Docker (Recommended)

```bash
# Clone the repository
git clone https://github.com/dnscz/praxe-lab-api-backend.git
cd praxe-lab-api-backend

# Copy environment file
cp .env.example .env

# Build and start containers
docker compose build
docker compose up -d

# Install dependencies
docker compose run --rm app composer install

# Generate application key
docker compose run --rm app php artisan key:generate

# Run migrations
docker compose run --rm app php artisan migrate

# Run tests to verify installation
docker compose run --rm app ./vendor/bin/pest
```

### Without Docker

```bash
# Clone and install
git clone https://github.com/dnscz/praxe-lab-api-backend.git
cd praxe-lab-api-backend
composer install

# Configure
cp .env.example .env
php artisan key:generate

# Database (SQLite by default)
touch database/database.sqlite
php artisan migrate

# Verify
./vendor/bin/pest
```

## API Documentation

Once running, access the auto-generated documentation:

- **Swagger UI**: [http://localhost:8080/docs/api](http://localhost:8080/docs/api)
- **OpenAPI JSON**: [http://localhost:8080/docs/api.json](http://localhost:8080/docs/api.json)

## Authentication

This kit uses **Laravel Sanctum** with token-based authentication.



### Login

```bash
curl -X POST http://localhost:8080/api/v1/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

### Using the Token

Include the token in the `Authorization` header for protected routes:



## API Endpoints

### Version 1 (`/api/v1`)

| Method | Endpoint                     | Auth | Description                   | Rate Limit |
|--------|------------------------------|------|-------------------------------|------------|
| POST   | /register                    | No   | Register new user             | 5/min      |
| POST   | /login                       | No   | Get authentication token      | 5/min      |
| POST   | /logout                      | Yes  | Revoke current token          | 120/min    |
| GET    | /me                          | Yes  | Get current user profile      | 120/min    |
| POST   | /email/verify/{id}/{hash}    | Yes  | Verify email address          | 120/min    |
| POST   | /email/resend                | Yes  | Resend verification email     | 6/min      |
| POST   | /forgot-password             | No   | Request password reset link   | 6/min      |
| POST   | /reset-password              | No   | Reset password with token     | 6/min      |

The rest of the endpoints is described in Swagger UI or Postman collection in this repository.

## Response Format

All API responses follow a consistent format:

### Success Response

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // Response data here
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200  | Success |
| 201  | Resource created |
| 204  | No content |
| 400  | Bad request |
| 401  | Unauthorized |
| 403  | Forbidden |
| 404  | Not found |
| 422  | Validation error |
| 429  | Too many requests |
| 500  | Server error |

## API Versioning

This kit uses [grazulex/laravel-apiroute](https://github.com/Grazulex/laravel-apiroute) v2.x for API versioning with support for:

- **URI Path** (default): `/api/v1/users`, `/api/v2/users`
- **Header**: `X-API-Version: 2`
- **Query Parameter**: `?api_version=2`
- **Accept Header**: `Accept: application/vnd.api.v2+json`


### Deprecation Headers

When accessing deprecated versions, responses include RFC-compliant headers:

```http
Deprecation: Sun, 01 Jun 2025 00:00:00 GMT
Sunset: Mon, 01 Dec 2025 00:00:00 GMT
Link: </api/v2>; rel="successor-version"
```

## Query Building

Use [spatie/laravel-query-builder](https://spatie.be/docs/laravel-query-builder) for filtering, sorting, and including relationships:


**Request examples:**
```
GET /api/v1/users?filter[name]=john
GET /api/v1/users?sort=-created_at
GET /api/v1/users?include=posts,comments
GET /api/v1/users?filter[name]=john&sort=name&include=posts
```



## Rate Limiting

| Limiter | Limit | Use Case |
|---------|-------|----------|
| `api` | 60/min | Default for all API routes |
| `auth` | 5/min | Login/register (brute force protection) |
| `authenticated` | 120/min | Logged-in users |

### Rate Limit Headers

Responses include rate limit information:

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
Retry-After: 60  # When limit exceeded
```


## Development Commands

```bash
# List all routes
docker compose run --rm app php artisan route:list

# Clear all caches
docker compose run --rm app php artisan optimize:clear

# Generate IDE helper files (if using Laravel IDE Helper)
docker compose run --rm app php artisan ide-helper:generate
docker compose run --rm app php artisan ide-helper:models -N

# Export OpenAPI spec to file
docker compose run --rm app php artisan scramble:export
```

## Environment Configuration

Key `.env` variables:

```env
# Application
APP_NAME="Laravel API Kit"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

# Database (SQLite for development)
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/database/database.sqlite

# For MySQL/PostgreSQL
# DB_CONNECTION=mysql
# DB_HOST=mysql
# DB_PORT=3306
# DB_DATABASE=laravel_api_kit
# DB_USERNAME=laravel
# DB_PASSWORD=secret

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1

# API Versioning
API_VERSION_STRATEGY=uri
API_DEFAULT_VERSION=latest

# Rate Limiting
API_RATE_LIMIT=60

# Documentation
API_DOCS_URL=http://localhost:8080/docs/api
```

## Deployment

### Production Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Configure proper database (MySQL/PostgreSQL)
- [ ] Set `APP_URL` to your production URL
- [ ] Configure `SANCTUM_STATEFUL_DOMAINS` for your frontend domains
- [ ] Review and tighten CORS settings in `config/cors.php`
- [ ] Set up proper rate limiting for production load
- [ ] Configure caching (Redis recommended)
- [ ] Set up queue worker for background jobs
- [ ] Enable HTTPS and update URLs

### Docker Production

```dockerfile
# Example production Dockerfile additions
FROM php:8.3-fpm-alpine

# Install opcache for performance
RUN docker-php-ext-install opcache

# Production PHP settings
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/
COPY docker/php/php.ini /usr/local/etc/php/conf.d/
```

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

- [Laravel](https://laravel.com) - The PHP Framework
- [Laravel Sanctum](https://laravel.com/docs/sanctum) - API Token Authentication
- [grazulex/laravel-apiroute](https://github.com/Grazulex/laravel-apiroute) - API Versioning
- [spatie/laravel-query-builder](https://github.com/spatie/laravel-query-builder) - Query Building
- [spatie/laravel-data](https://github.com/spatie/laravel-data) - Data Transfer Objects
- [dedoc/scramble](https://github.com/dedoc/scramble) - API Documentation
- [grazulex/laravel-api-idempotency](https://github.com/Grazulex/laravel-api-idempotency) - API Idempotency (optional)
- [grazulex/laravel-api-throttle-smart](https://github.com/Grazulex/laravel-api-throttle-smart) - Smart Rate Limiting (optional)
- [Pest PHP](https://pestphp.com) - Testing Framework
- [grazulex/laravel-api-kit](https://github.com/Grazulex/laravel-api-kit) - Base API kit
