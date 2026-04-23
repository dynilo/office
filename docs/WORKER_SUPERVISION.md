# Worker Supervision Baseline

The runtime worker baseline is intentionally host-neutral. Use one process manager per deployment, but keep the command aligned with `config/workers.php`.

The default execution worker command is:

```bash
php artisan queue:work redis --queue=executions --tries=3 --sleep=3 --timeout=60 --max-jobs=500 --max-time=3600 --memory=256 --backoff=5,30,120
```

## Environment

```dotenv
WORKER_EXECUTION_NAME=ai-office-execution-worker
WORKER_QUEUE_CONNECTION=redis
WORKER_QUEUE=executions
WORKER_TRIES=3
WORKER_BACKOFF_SECONDS=5,30,120
WORKER_SLEEP_SECONDS=3
WORKER_TIMEOUT_SECONDS=60
WORKER_MAX_JOBS=500
WORKER_MAX_TIME_SECONDS=3600
WORKER_MEMORY_MB=256
WORKER_STOP_WHEN_EMPTY=false
```

`WORKER_QUEUE_CONNECTION` and `WORKER_QUEUE` should match `RUNTIME_EXECUTION_QUEUE_CONNECTION` and `RUNTIME_EXECUTION_QUEUE`.

## Generic Shell

Use this for local verification or for process managers that accept a raw command:

```bash
php artisan queue:work redis --queue=executions --tries=3 --sleep=3 --timeout=60 --max-jobs=500 --max-time=3600 --memory=256 --backoff=5,30,120
```

## Supervisor Template

```ini
[program:ai-office-execution-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/current/artisan queue:work redis --queue=executions --tries=3 --sleep=3 --timeout=60 --max-jobs=500 --max-time=3600 --memory=256 --backoff=5,30,120
directory=/path/to/current
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/ai-office/execution-worker.log
stopwaitsecs=70
```

## systemd Template

```ini
[Unit]
Description=AI Office OS execution queue worker
After=network.target

[Service]
Type=simple
WorkingDirectory=/path/to/current
ExecStart=/usr/bin/php artisan queue:work redis --queue=executions --tries=3 --sleep=3 --timeout=60 --max-jobs=500 --max-time=3600 --memory=256 --backoff=5,30,120
Restart=always
RestartSec=5
TimeoutStopSec=70
KillSignal=SIGTERM

[Install]
WantedBy=multi-user.target
```

## Readiness

`App\Support\Workers\WorkerSupervisionReadiness` inspects configuration only. It does not connect to Redis, start workers, or assume a host init system. It verifies that the configured worker is bounded by restart limits and aligned with the runtime execution queue.

For runtime validation of supervised worker expectations, run:

```bash
php artisan workers:validate-runtime
```

This runtime validation checks:
- the configured worker command contract
- queue/runtime alignment
- optional supervisor evidence from a status output file
- optional process evidence from a process snapshot file

Optional environment inputs for runtime validation:

```dotenv
WORKER_SUPERVISION_DRIVER=none
WORKER_REQUIRE_SUPERVISION_IN_PRODUCTION=false
WORKER_SUPERVISOR_STATUS_PATH=
WORKER_PROCESS_SNAPSHOT_PATH=
```

Safe fallback behavior:
- if no real supervisor exists in the current environment, validation can still pass in non-production or when supervision is not strictly required
- if a status/process file is provided, the validator confirms the expected worker command is actually represented there
- no supervisor service is started or controlled by the application
