# Deployment Baseline

This repository currently ships a CI/CD baseline for verification, not a full deployment pipeline.

## Production assumptions

- PHP 8.4+
- PostgreSQL as the default production database
- Redis for queue and cache workloads
- A writable local filesystem or configured object storage for runtime files

## Release verification

Run the same checks locally or in automation before a deploy:

```bash
composer validate --strict
./vendor/bin/pint --test
php artisan about --only=environment,cache,drivers
php artisan test
```

## Deployment sequence

Use a simple immutable-style release flow:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache
php artisan queue:restart
```

## Runtime readiness

- Keep `DB_CONNECTION=pgsql` in production.
- Keep `QUEUE_CONNECTION=redis` in production.
- Run the execution worker against the configured runtime queue.
- Verify `/api/health` after each deploy.

## Scope note

This slice adds repository automation and deploy-readiness guidance only. It does not add hosting-specific infrastructure, container orchestration, or a production deploy target.
