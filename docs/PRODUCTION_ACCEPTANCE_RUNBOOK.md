# Production Acceptance Runbook

This runbook defines the exact end-to-end process for declaring the AI Office OS repository production-beta ready.

It is written for operators validating a real deployment candidate, not for local feature development.

## Release gate

Declare the repository production-beta ready only if:
- every required check in this runbook passes
- all blocking failures are resolved
- all fallback modes left enabled are intentional, documented, and accepted by the release owner

If any required check fails, stop the acceptance run and do not approve the release.

---

## 1. Pre-flight

Run this section before touching runtime validation.

### Confirm repository state

Check:
- `main` is clean
- all intended commits are pushed
- the release candidate version/commit is known
- production env files and secrets are present

Run:

```bash
git status --short
git rev-parse --short HEAD
php artisan test
php artisan about
```

Pass criteria:
- working tree is clean
- test suite is green
- application boots successfully

Stop if:
- tests fail
- app bootstrap fails
- the release candidate is not clearly identified

---

## 2. Infrastructure Validation Sequence

Run infrastructure checks in this order.

### 2.1 PostgreSQL

Run:

```bash
php artisan postgresql:validate-runtime
```

Validate:
- PostgreSQL connection succeeds
- runtime database is the expected database
- schema/search-path assumptions are correct
- runtime tables are reachable

Pass criteria:
- command exits successfully
- no critical readiness check is false

### 2.2 Redis

Run:

```bash
php artisan redis:validate-runtime
```

Validate:
- Redis queue path is reachable
- cache path is reachable or intentionally using a safe non-Redis fallback
- broadcast path is reachable or intentionally using a safe fallback

Pass criteria:
- command exits successfully
- any fallback mode is explicit and acceptable for the target environment

### 2.3 pgvector

Run:

```bash
php artisan pgvector:validate-runtime
```

Validate:
- pgvector extension is available
- vector column assumptions are correct
- similarity-search capability is available
- configured embedding dimensions match storage expectations

Pass criteria:
- command exits successfully for real memory/search readiness
- if not ready, the release cannot be declared production-beta for real retrieval use

### 2.4 Storage strategy

Validate manually:
- document storage disk/path is correct
- artifact storage disk/path is correct
- runtime paths are writable
- backup target paths exist or are provisioned

Pass criteria:
- runtime file paths are explicit, writable, and persistent

---

## 3. Auth And Role Validation Sequence

### 3.1 Runtime auth validation

Run:

```bash
php artisan auth:validate-runtime
```

Validate:
- login and logout routes exist
- admin web routes require auth and role middleware
- admin API routes require auth and role middleware
- expected admin roles are enforced

Pass criteria:
- command exits successfully

### 3.2 Manual auth checks

Validate in the deployed environment:
- login with a valid operational user
- logout clears access
- unauthenticated access to `/admin/*` is blocked
- unauthenticated access to protected `/api/*` and `/api/admin/*` is blocked
- non-admin authenticated users are blocked from admin surfaces

Pass criteria:
- protected surfaces are not exposed
- role enforcement matches operator expectations

Stop if:
- any protected route is accessible without the expected auth/role checks

---

## 4. Queue And Worker Validation Sequence

### 4.1 Worker process validation

Run:

```bash
php artisan workers:validate-runtime
```

Validate:
- configured worker command is correct
- supervision assumptions are explicit
- process evidence exists if supervision is required

Pass criteria:
- command exits successfully
- production is not relying on an unintended unsupervised fallback

### 4.2 Real queue execution validation

Perform an end-to-end runtime check:

1. Ensure at least one active agent exists for a runnable task path.
2. Create a queued task through the API or the admin UI.
3. Start the appropriate worker process if not already running.
4. Confirm the task leaves the queue.
5. Confirm execution records are created and updated.
6. Confirm logs are written for the execution.

Validate:
- queued work is consumed
- task status changes are coherent
- execution status changes are coherent
- retries and failures are visible if triggered

Pass criteria:
- workers process jobs correctly on real infrastructure

Stop if:
- queued work stalls
- worker supervision is unstable
- status transitions are inconsistent

---

## 5. Document, Knowledge, And Memory Validation Sequence

Run this sequence on real storage and real database infrastructure.

### 5.1 Document ingestion

Validate:
1. Upload a representative document.
2. Confirm document metadata is stored.
3. Confirm raw extracted text is persisted.

### 5.2 Knowledge extraction

Validate:
1. Trigger document-to-knowledge extraction.
2. Confirm deterministic chunks are created.
3. Confirm chunk metadata is present.

### 5.3 Embedding and retrieval readiness

Run:

```bash
php artisan embedding-provider:validate-runtime
php artisan retrieval:validate-quality
```

If real retrieval is in scope, also require:

```bash
php artisan pgvector:validate-runtime
```

Validate:
- real embedding provider is ready
- retrieval thresholds are explicit
- retrieval ordering is deterministic
- safe empty-result behavior is preserved
- similarity search works for real memory-backed retrieval

Pass criteria:
- document ingestion, knowledge extraction, and retrieval all work end-to-end
- degraded fallback is explicit only where intentionally accepted

Stop if:
- documents ingest but cannot progress through knowledge/memory flow
- retrieval quality is unsafe or unconfigured

---

## 6. Company Loop Acceptance Sequence

This is the primary end-to-end product acceptance path.

### 6.1 Synthetic production validation

Run:

```bash
php artisan company-loop:validate-production
```

Validate:
- active coordinator exists
- required specialists exist
- prompt version is explicit
- end-to-end synthetic probe succeeds
- validation rolls probe data back cleanly

Pass criteria:
- command exits successfully

### 6.2 Real operator flow

Run one real acceptance scenario:

1. Submit a high-level goal from the company loop UI or the approved runtime entry point.
2. Confirm coordinator intent task creation.
3. Confirm decomposition creates child tasks.
4. Confirm child tasks are assigned to the correct specialist roles.
5. Confirm executions run successfully.
6. Confirm artifacts are stored for child outputs.
7. Confirm final coordinator report artifact is stored.
8. Confirm communication logs are present.
9. Confirm audit records are present.

Validate:
- parent task exists
- child tasks exist and are linked correctly
- outputs are persisted
- final report is visible and coherent

Pass criteria:
- one real company loop completes end-to-end on the target environment

Stop if:
- decomposition is incorrect
- specialist outputs do not persist
- final report is missing or inconsistent

---

## 7. Dashboard And Admin Operational Checks

Validate the operator-facing surfaces after infrastructure and runtime checks are green.

Pages to inspect:
- login
- dashboard
- agents
- tasks
- executions
- audit visibility
- documents / knowledge
- company loop page

Validate:
- pages render without auth leaks
- counts and summaries look credible
- latest task/execution/audit activity is visible
- execution details and logs are visible
- agent and task administration still works
- document and company loop surfaces are usable

Operational spot checks:
- a newly created task appears in the admin task views
- a recent execution appears in the execution monitor
- recent audit events are queryable
- company loop results are visible from the admin experience

Pass criteria:
- admin surfaces are usable by operators without broken critical flows

---

## 8. Backup And Restore Checks

### 8.1 Manifest validation

Run:

```bash
php artisan backup:manifest
php artisan restore:manifest
```

Validate:
- database backup intent is explicit
- Redis backup intent is explicit
- runtime file backup intent is explicit
- restore order is explicit

### 8.2 Operational backup check

Confirm operationally:
- PostgreSQL dump location/command is valid
- Redis snapshot approach is valid
- runtime file paths in backup scope are correct
- someone operating the release can execute the restore sequence

Pass criteria:
- backup and restore flow is documented, understood, and executable

Stop if:
- backup scope is unclear
- restore order is unclear
- runtime file recovery is undefined

---

## 9. CI/CD Verification Checks

### 9.1 Repository CI baseline

Run:

```bash
composer validate --strict
./vendor/bin/pint --test
php artisan about --only=environment,cache,drivers
php artisan test
```

Validate:
- Composer metadata is valid
- code style is clean
- app bootstrap is healthy
- tests are green

### 9.2 Automation review

Confirm:
- `.github/workflows/ci.yml` is present
- CI runs the expected baseline checks
- the workflow targets the intended PHP version

Pass criteria:
- the repository can be validated automatically and manually using the same baseline

---

## 10. Release Decision Criteria

Approve production-beta only if all of the following are true:
- infrastructure validation passed
- auth and role validation passed
- queue and worker validation passed
- document/knowledge/memory validation passed
- company loop acceptance passed
- dashboard/admin checks passed
- backup/restore checks passed
- CI/CD verification passed
- no blocking severity production issue remains open

Release owner must record:
- release candidate commit SHA
- validation date/time
- validating operator
- accepted fallback modes, if any
- open non-blocking follow-ups

Do not approve production-beta if:
- any required validation command fails
- any critical runtime flow is still simulated when it is expected to be real
- rollback cannot be executed safely

---

## 11. Rollback Readiness Checklist

Confirm before release approval:
- previous known-good release identifier is recorded
- previous environment/config snapshot is available
- database restore procedure is available and tested enough for operator confidence
- Redis recovery procedure is available
- runtime file restore scope is known
- worker restart procedure is known
- health verification steps after rollback are known

Minimum rollback sequence:

1. Put the application into maintenance mode if needed.
2. Re-deploy the previous known-good release.
3. Restore database state if forward changes require it.
4. Restore Redis state if continuity matters.
5. Restore runtime files if they changed incompatibly.
6. Restart workers.
7. Verify:
   - `php artisan about`
   - `/api/health`
   - admin login
   - one critical task/execution flow

Rollback pass criteria:
- previous release can be restored without undefined manual improvisation

---

## 12. Final Sign-Off Record

The operator signing off production-beta readiness should capture:
- environment name
- release SHA/tag
- date/time completed
- validator name
- passed sections
- accepted fallbacks
- unresolved non-blocking issues
- explicit go / no-go decision

If any section above is not completed, the decision is automatically `no-go`.
