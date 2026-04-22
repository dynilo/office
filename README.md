# AI Office OS

Minimal Laravel 13 bootstrap for an API-first AI Office OS.

## Stack

- PHP 8.4+
- Laravel 13
- PostgreSQL
- Redis
- Pest

## Slice 01 scope

- API bootstrap only
- PostgreSQL and Redis configured through environment variables
- Health endpoint at `GET /api/health`
- No business modules, agent runtime, or domain tables yet

## Local usage

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan about
php artisan test
```
