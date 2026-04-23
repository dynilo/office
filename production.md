# production.md

You are now working in productionization mode for this repository.

## Primary goal
Turn the completed 60-slice system into a production-ready operating system step by step, without destabilizing the existing architecture.

## Documents to read first
- AGENTS.md
- docs/POST_60_STABILIZATION.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

## Operating mode
- Work in strict sequential productionization mode
- Complete only one production step per run
- Do not batch unrelated work
- Do not redesign working subsystems unless required for production safety
- Prefer minimal, production-safe, test-covered changes

## Priority order
1. Real infrastructure alignment
   - PostgreSQL
   - Redis
   - pgvector
   - queue workers
   - storage strategy
2. Security hardening
   - auth verification
   - role and permission hardening
   - secret safety
   - rate limits and abuse protection
3. Runtime reliability
   - retry correctness
   - dead-letter handling
   - worker supervision
   - observability
4. AI and runtime realism
   - real provider verification
   - real embedding provider
   - retrieval quality checks
   - prompt, version, and cost tracking verification
5. Product readiness
   - dashboard polish
   - unified detail visibility
   - company loop production acceptance
   - CI/CD
   - backup and restore

## Mandatory execution rules
1. Identify the single most critical unfinished productionization step.
2. Execute one and only one productionization step in this run.
3. Do not begin the following production step.
4. Do not merge multiple production steps into one implementation.
5. Stay strictly inside the current production step scope.
6. Do not refactor unrelated areas.
7. Do not redesign unrelated architecture.
8. Generate complete working code, not pseudo-code.
9. Add or update tests required by the current production step.
10. If infrastructure is unavailable in the current environment, implement safe fallback behavior and make assumptions explicit.

## Required end-of-step output
When the current production step is complete, you must:
1. run the relevant tests
2. summarize changed files
3. summarize assumptions
4. summarize remaining production risks
5. provide the exact commit command in this format:
   git add .
   git commit -m "Complete Production Step XX ..."
   git push origin main
6. stop after the current production step

## Prohibited behavior
- Do not jump ahead
- Do not work on future production steps
- Do not bundle multiple production steps together
- Do not keep going after finishing the current production step
- Do not expand scope because something feels related

## Step selection rule
- Read docs/POST_60_STABILIZATION.md
- Identify the next most critical unfinished productionization step
- Execute only that one
- Always stop after finishing exactly one production step

## If the current step is ambiguous
Use docs/POST_60_STABILIZATION.md as the source of truth.
Prefer minimal, production-oriented, test-covered implementation.