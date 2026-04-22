# BOOTSTRAP_PLAN.md

## Goal
Create the minimum production-oriented foundation for a Laravel 13 based AI Office OS.

## Slice 01 only
The first slice is only about foundation bootstrap.

### Required outputs
- Laravel application initialized
- PHP 8.4+ compatible setup
- PostgreSQL configured through environment variables
- Redis configured through environment variables
- Pest installed and working
- health endpoint
- health test
- minimal README

### Explicitly out of scope
Do not implement:
- business modules
- agent runtime
- task engine
- document ingestion
- UI
- Docker
- Kubernetes
- Horizon
- Octane
- speculative packages

## Expected result
At the end of Slice 01:
- `php artisan test` passes
- `php artisan about` works
- `/api/health` returns success JSON
