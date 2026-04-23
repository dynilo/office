# Redis Queue Production Layer

The runtime queue is Redis-first in production. Tests may use `sync`, but production should use Redis so workers can be supervised independently from web requests.

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

`RedisQueueProductionReadiness` inspects configuration only. It does not open a Redis connection, so it is safe in build and CI environments.

Set `QUEUE_ENFORCE_REDIS_IN_PRODUCTION=false` only for controlled maintenance or non-standard deployments.
