# Redis Runtime Production Layer

The runtime queue is Redis-first in production. Cache and broadcasting may also use Redis, but each path should declare a safe fallback when Redis is unavailable in local, CI, or maintenance environments.

Production defaults:
- `QUEUE_CONNECTION=redis`
- `REDIS_QUEUE_CONNECTION=default`
- `REDIS_QUEUE=default`
- `REDIS_QUEUE_RETRY_AFTER=90`
- `REDIS_QUEUE_BLOCK_FOR=5`
- `REDIS_QUEUE_AFTER_COMMIT=true`
- `RUNTIME_EXECUTION_QUEUE_CONNECTION=redis`
- `RUNTIME_EXECUTION_QUEUE=executions`
- `RUNTIME_EXECUTION_TRIES=3`
- `RUNTIME_EXECUTION_BACKOFF_SECONDS=5,30,120`
- `CACHE_STORE=redis`
- `REDIS_CACHE_CONNECTION=cache`
- `REDIS_CACHE_LOCK_CONNECTION=default`
- `BROADCAST_CONNECTION=log`
- `REDIS_BROADCAST_CONNECTION=default`

`RedisQueueProductionReadiness` inspects configuration only. It does not open a Redis connection, so it is safe in build and CI environments.

Set `QUEUE_ENFORCE_REDIS_IN_PRODUCTION=false` only for controlled maintenance or non-standard deployments.

For live runtime validation against the configured Redis connections, run:

```bash
php artisan redis:validate-runtime
```

This runtime validation attempts a real Redis `PING` only for the queue, cache, and broadcast paths that are actually configured to use Redis.

Fallback-safe modes:
- queue: non-Redis only outside production or when queue Redis enforcement is intentionally disabled
- cache: any non-Redis default store is considered a safe fallback
- broadcast: `log` and `null` are safe fallback modes

When Redis is unavailable, the command fails safely with structured JSON output and redacted connection errors.
