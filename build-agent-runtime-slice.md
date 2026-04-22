# SLICE_ROADMAP.md

## Phase 1 — Foundation

### Slice 01 — Repo Bootstrap
Goal:
Create the initial Laravel 13 project foundation for an API-first AI Office OS.

Required outcomes:
- Laravel app from scratch
- PostgreSQL config via env
- Redis config via env
- Pest installed
- health endpoint
- health test
- minimal README

Acceptance:
- `php artisan test` passes
- `php artisan about` works
- `/api/health` returns success JSON
- no business modules exist yet

### Slice 02 — Core Domain Skeleton
Goal:
Create the top-level layered structure and foundational contracts/enums/value objects.

### Slice 03 — Database Schema v1
Goal:
Create the first runtime schema for agents, tasks, executions, documents, and knowledge items.

### Slice 04 — Agent Registry API
Goal:
Manage agent definitions through API.

### Slice 05 — Task Intake API
Goal:
Accept structured tasks into the system.

Continue one slice at a time.
Do not move to the next slice until the current slice is complete and verified.
