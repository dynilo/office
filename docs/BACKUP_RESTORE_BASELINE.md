# Backup And Restore Baseline

This repository includes a backup and restore baseline for critical runtime data and files.

## Covered surfaces

- PostgreSQL runtime data
- Redis queue and cache state
- Runtime document files
- Runtime artifact files

## Backup verification

Inspect the generated baseline plans:

```bash
php artisan backup:manifest
php artisan restore:manifest
```

## Baseline backup sequence

```bash
php artisan down
php artisan backup:manifest
pg_dump --format=custom --file=storage/app/private/backups/database.dump
redis-cli --rdb storage/app/private/backups/redis.rdb
php artisan up
```

Copy the runtime document and artifact directories from their configured disks alongside the database and Redis snapshots.

## Baseline restore sequence

```bash
php artisan down
php artisan restore:manifest
pg_restore --clean --if-exists --no-owner --dbname=${DB_DATABASE} storage/app/private/backups/database.dump
php artisan up
php artisan queue:restart
```

If Redis continuity matters, restore the Redis snapshot out-of-band before restarting workers.

## Scope note

This slice adds backup and restore support surfaces, planning output, and documentation only. It does not add a managed backup service, remote snapshot orchestration, or automated restore execution.
