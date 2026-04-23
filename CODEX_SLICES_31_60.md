# Codex Slices 31–60

Bu dosya Slice 31–60 için hazır prompt setidir.

## Standart akış

```bash
php artisan test
git add .
git commit -m "Complete Slice XX ..."
git push origin main
```


## Slice 31 — Auth Foundation

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 31 — Auth Foundation.

Task:
Implement authentication foundation for the AI Office OS admin layer.

Goal:
Protect the admin shell and prepare authenticated access for future operational actions.

Create:
- Laravel auth foundation using the simplest production-safe built-in approach
- login/logout flow
- authenticated middleware protection for /admin routes
- guest/auth redirects
- tests for guest blocking and authenticated access

Constraints:
- do not redesign existing admin pages
- do not add social auth
- do not add RBAC yet
- keep this slice focused on basic authentication only

Acceptance criteria:
- unauthenticated users cannot access /admin pages
- authenticated users can access the admin shell
- login/logout works
- tests pass

Important execution rules:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 31 auth foundation"
git push origin main
```


## Slice 32 — Role and Permission Layer

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 32 — Role and Permission Layer.

Task:
Implement the first role and permission layer.

Goal:
Introduce internal roles for operational access control.

Create:
- role model/schema if needed
- permission model/schema if needed
- user-role assignment
- simple guards/policies for admin access levels
- tests for role-protected access

Target roles:
- super_admin
- admin
- operator
- observer

Constraints:
- do not redesign auth
- do not add team/org tenancy yet
- keep this slice focused on internal access control only

Acceptance criteria:
- protected routes can be restricted by role
- users can be assigned roles
- tests pass

Important execution rules:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 32 role and permission layer"
git push origin main
```


## Slice 33 — API Authentication

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 33 — API Authentication.

Task:
Protect the runtime/admin API with authenticated access.

Goal:
Require authenticated API access for protected endpoints while preserving any intentionally public endpoints.

Create:
- token or session-based API auth using Laravel-native tooling
- middleware protection for protected API groups
- tests for authenticated vs unauthenticated API access

Constraints:
- do not redesign frontend shell routes
- do not add OAuth/social login
- keep this slice focused on API protection only

Acceptance criteria:
- protected APIs reject unauthenticated access
- authenticated access works
- tests pass

Important execution rules:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 33 api authentication"
git push origin main
```


## Slice 34 — Audit Hardening

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 34 — Audit Hardening.

Task:
Harden audit coverage for authenticated user actions.

Goal:
Extend audit events so UI/API operational actions include authenticated actor context.

Create:
- actor resolution from authenticated users
- audit coverage for key admin mutations
- tests for authenticated audit actor capture

Constraints:
- do not redesign the existing audit event schema unless required
- keep this slice focused on authenticated audit enrichment only

Acceptance criteria:
- authenticated mutations store user actor context in audit events
- tests pass

Important execution rules:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 34 audit hardening"
git push origin main
```


## Slice 35 — Secret Management Baseline

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 35 — Secret Management Baseline.

Task:
Implement a baseline secret-management and configuration-safety layer.

Goal:
Reduce accidental secret exposure and centralize sensitive runtime configuration handling.

Create:
- configuration wrappers or safety helpers for provider secrets
- secret redaction rules where needed
- tests for secret-safe behavior

Constraints:
- do not introduce external vault infrastructure yet
- keep this slice focused on application-level secret safety only

Acceptance criteria:
- secrets are not exposed in logs/responses/config debug surfaces
- tests pass

Important execution rules:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 35 secret management baseline"
git push origin main
```


## Slice 36 — PostgreSQL Production Alignment

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 36 — PostgreSQL Production Alignment.

Task:
Align project behavior more explicitly with PostgreSQL as the production target. Create config guards, production-safe assumptions, and tests/documentation for PostgreSQL-first behavior without requiring a live PostgreSQL server in the current environment.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 36 postgresql production alignment"
git push origin main
```


## Slice 37 — pgvector Production Readiness

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 37 — pgvector Production Readiness.

Task:
Improve pgvector production readiness. Create stronger capability checks, clearer setup assumptions, optional diagnostics, and tests for readiness/fallback behavior without requiring real pgvector in the current environment.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 37 pgvector production readiness"
git push origin main
```


## Slice 38 — Redis Queue Production Layer

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 38 — Redis Queue Production Layer.

Task:
Strengthen the queue layer for production Redis usage. Create explicit queue config alignment, production-safe defaults, and tests for queue-configuration-sensitive behavior.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 38 redis queue production layer"
git push origin main
```


## Slice 39 — Worker Supervision Baseline

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 39 — Worker Supervision Baseline.

Task:
Create the first worker supervision baseline. Add in-repo worker process templates or docs, readiness indicators if useful, and tests where applicable, without assuming a single host init system.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 39 worker supervision baseline"
git push origin main
```


## Slice 40 — Storage Strategy Hardening

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 40 — Storage Strategy Hardening.

Task:
Harden storage strategy for artifacts and documents. Make disk/path intent explicit and keep current flows working, without implementing full cloud storage integration yet.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 40 storage strategy hardening"
git push origin main
```


## Slice 41 — Embedding Provider Implementation

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 41 — Embedding Provider Implementation.

Task:
Implement the first real embedding provider behind the existing embedding contract. Add normalized response handling, safe logging/redaction, and mocked provider tests.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 41 embedding provider implementation"
git push origin main
```


## Slice 42 — Retrieval Quality Controls

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 42 — Retrieval Quality Controls.

Task:
Improve retrieval quality controls. Add score thresholds, rerank-ready structure if useful, retrieval diagnostics, and tests for quality filtering behavior.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 42 retrieval quality controls"
git push origin main
```


## Slice 43 — Prompt Versioning

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 43 — Prompt Versioning.

Task:
Implement prompt versioning. Track and persist which prompt configuration/version was used for runtime executions, with tests for traceability.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 43 prompt versioning"
git push origin main
```


## Slice 44 — Provider Failover Layer

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 44 — Provider Failover Layer.

Task:
Implement provider failover logic. Add a provider routing/failover service, deterministic fallback rules, and tests for primary-to-secondary fallback behavior.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 44 provider failover layer"
git push origin main
```


## Slice 45 — Cost Tracking

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 45 — Cost Tracking.

Task:
Implement cost tracking. Track usage and estimated cost by provider, agent, task, and execution with persistence and tests, but do not build billing yet.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 45 cost tracking"
git push origin main
```


## Slice 46 — Dashboard Metrics UI

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 46 — Dashboard Metrics UI.

Task:
Upgrade the admin dashboard with useful runtime KPIs. Add cards/charts/tables over existing APIs and tests where applicable, without redesigning backend runtime flows.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 46 dashboard metrics ui"
git push origin main
```


## Slice 47 — Unified Task Detail UI

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 47 — Unified Task Detail UI.

Task:
Create a richer unified task detail experience. Show task core data plus executions, artifacts, audit events, and communication history in one operational view with tests where applicable.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 47 unified task detail ui"
git push origin main
```


## Slice 48 — Document and Knowledge UI

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 48 — Document and Knowledge UI.

Task:
Build UI for document ingestion and knowledge extraction visibility. Support upload via existing APIs and viewing extracted text and knowledge chunks, with tests where applicable.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 48 document and knowledge ui"
git push origin main
```


## Slice 49 — Agent Conversation UI

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 49 — Agent Conversation UI.

Task:
Build the first communication history UI for agent-to-agent messages. Add filters by task and agent pair and integrate existing communication query support.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 49 agent conversation ui"
git push origin main
```


## Slice 50 — Company Loop Run UI

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 50 — Company Loop Run UI.

Task:
Create the first UI surface for running the company loop. Add a high-level goal intake form, trigger surface, and result/report display over an application-facing entry point.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 50 company loop run ui"
git push origin main
```


## Slice 51 — Multi-Tenant Foundation

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 51 — Multi-Tenant Foundation.

Task:
Implement the first multi-tenant foundation. Add organization/tenant identity, tenant linkage for core runtime entities as appropriate, tenant scoping rules, and isolation tests.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 51 multi-tenant foundation"
git push origin main
```


## Slice 52 — Organization Settings Layer

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 52 — Organization Settings Layer.

Task:
Implement organization settings. Add per-organization settings for provider, memory, policy, and runtime defaults, plus tests for settings resolution.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 52 organization settings layer"
git push origin main
```


## Slice 53 — Policy Engine v1

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 53 — Policy Engine v1.

Task:
Implement policy engine v1. Add explicit policy rules controlling agent capabilities, access, or actions, plus enforcement hooks and tests.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 53 policy engine v1"
git push origin main
```


## Slice 54 — Human Approval Gates

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 54 — Human Approval Gates.

Task:
Implement human approval gates. Add approval-request persistence, approval decision flow, and tests for pausing/resuming gated runtime actions.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 54 human approval gates"
git push origin main
```


## Slice 55 — SLA and Dead-Letter Handling

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 55 — SLA and Dead-Letter Handling.

Task:
Implement SLA, timeout, and dead-letter handling. Add runtime policies for stuck jobs, expired work, dead-letter capture, and tests for failure handling behavior.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 55 sla and dead-letter handling"
git push origin main
```


## Slice 56 — Observability Layer

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 56 — Observability Layer.

Task:
Implement the first observability layer. Add structured operational metrics/logging/tracing hooks and tests or diagnostics where applicable.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 56 observability layer"
git push origin main
```


## Slice 57 — CI/CD Baseline

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 57 — CI/CD Baseline.

Task:
Implement a CI/CD baseline. Add repository automation for tests/lint/build verification and minimal deployment readiness docs/config, without overbuilding infra.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 57 ci/cd baseline"
git push origin main
```


## Slice 58 — Backup and Restore Baseline

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 58 — Backup and Restore Baseline.

Task:
Implement backup and restore baseline. Add documented and code-level support surfaces for backing up and restoring critical runtime data and files, with tests where practical.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 58 backup and restore baseline"
git push origin main
```


## Slice 59 — Usage Accounting Foundation

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 59 — Usage Accounting Foundation.

Task:
Implement usage accounting foundation. Add internal accounting for per-org, per-user, per-agent, or per-runtime usage totals, distinct from provider cost tracking.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 59 usage accounting foundation"
git push origin main
```


## Slice 60 — External Integrations Gateway

```text
Read first:
- AGENTS.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

Execute Slice 60 — External Integrations Gateway.

Task:
Implement an external integrations gateway. Add a clean abstraction layer for future Gmail/Slack/Drive/ERP/CRM style connectors with tests and one minimal stub integration.

Constraints:
- Work only within the requested slice.
- Do not expand scope.
- Do not redesign unrelated parts.
- Generate complete working code, not pseudo-code.
- Add or update tests for the slice.
- At the end, summarize changed files and assumptions.
```

Commit:
```bash
git add .
git commit -m "Complete Slice 60 external integrations gateway"
git push origin main
```
