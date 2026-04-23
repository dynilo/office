# Production Steps 05–14

This document defines the fixed productionization sequence after Production Steps 01–04.

## Usage
For each production step:
1. Execute exactly one step
2. Run relevant tests
3. Summarize changed files, assumptions, and remaining risks
4. Commit and push
5. Stop

---

## Production Step 05 — Auth and Role Hardening Validation
Validate and harden authentication and authorization behavior for production operation.

Goal:
Make auth and role enforcement explicit, verify protected surfaces, and document safe production assumptions.

---

## Production Step 06 — Real Embedding Provider Validation
Validate and harden the repository for real embedding-provider-backed runtime operation.

Goal:
Make embedding provider readiness explicit and ensure safe fallback behavior.

---

## Production Step 07 — Retrieval Quality Validation
Validate and harden retrieval quality for production use.

Goal:
Ensure retrieval filtering, thresholds, and result quality are explicit and safe.

---

## Production Step 08 — Dashboard and Operational Visibility Refinement
Refine operational visibility for production use.

Goal:
Make dashboard and runtime visibility usable for real operators.

---

## Production Step 09 — Company Loop Production Acceptance
Validate the company loop end-to-end under production assumptions.

Goal:
Ensure coordinator → decomposition → specialist → report flow is production-safe.

---

## Production Step 10 — CI/CD Baseline
Create the first CI/CD baseline.

Goal:
Ensure repository can be tested and validated automatically.

---

## Production Step 11 — Backup and Restore Baseline
Create backup and restore foundation.

Goal:
Make critical data recoverable.

---

## Production Step 12 — Observability Layer
Implement basic observability.

Goal:
Make runtime diagnosable.

---

## Production Step 13 — Usage and Accounting Hardening
Harden usage and accounting behavior.

Goal:
Make usage tracking reliable for future billing.

---

## Production Step 14 — External Integration Readiness
Prepare system for external integrations.

Goal:
Make integration gateway production-ready.