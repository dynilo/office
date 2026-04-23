# Post-60 Stabilization Checklist

This document defines the order of work after all 60 slices are complete, so the system can move from a strong core platform into a production-ready operating system.

## 1. Repository and Release Stabilization
- Verify that the `main` branch is clean
- Verify that all commits are pushed to GitHub
- Run `php artisan test` one final time
- Create a release tag such as `v0.1.0-core`
- Review `.env.example` and create a production-oriented environment template

## 2. Production Infrastructure Validation
- Install and validate real PostgreSQL
- Install and validate real Redis
- Install and validate `pgvector`
- Confirm queue workers are actually running
- Confirm storage disk strategy for documents and artifacts
- Confirm worker supervision strategy

## 3. Security Validation
- Validate the login/logout flow in production mode
- Validate role and permission enforcement
- Confirm that secrets do not leak into logs or responses
- Review `APP_KEY`, cookies, sessions, and environment handling
- Add or verify rate limiting and brute-force protection

## 4. Operational Validation
- Confirm queue workers process jobs correctly
- Confirm retry behavior works under real failures
- Confirm audit events are written consistently
- Confirm broadcast fallback and real broadcast behavior
- Run end-to-end checks for artifacts, documents, and knowledge flows

## 5. LLM and Memory Validation
- Connect a real embedding provider
- Validate retrieval quality
- Validate provider fallback behavior
- Validate usage and cost tracking
- Validate prompt version tracking

## 6. UI Validation
- Admin login/logout
- Agent management UI
- Task queue UI
- Execution monitor UI
- Dashboard and unified detail views
- Document / knowledge UI
- Company loop UI

## 7. End-to-End Acceptance
- Coordinator intake works
- Decomposition creates the correct child tasks
- Specialist agent outputs are persisted
- Final company loop report artifact is created
- Communication log and audit trail are complete

## 8. CI/CD and Maintenance
- Test pipeline
- Build/deploy pipeline
- Backup and restore flow
- Log rotation and disk usage checks
- Rollback plan

## 9. Initial Production Backlog
- Hardening tenant isolation
- Human approval gates
- Policy engine hardening
- Observability metrics and tracing
- Billing and usage dashboard
- External integrations gateway

## 10. Recommended Execution Order
1. Real PostgreSQL + Redis + pgvector setup
2. Worker supervision and queue validation
3. Auth and role hardening
4. Embedding provider and retrieval quality validation
5. Dashboard and operational visibility refinement
6. Company loop production acceptance test
7. CI/CD + backup/restore + rollout preparation

## Exit Criteria
The system can be considered production-ready when:
- tests are clean
- core runtime flows work on real infrastructure
- baseline security checks are complete
- audit and observability are present
- the company loop is validated end-to-end