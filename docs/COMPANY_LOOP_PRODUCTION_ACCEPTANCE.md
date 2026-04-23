# Company Loop Production Acceptance

The company loop is production-ready only if the full coordinator to specialist workflow can run safely under real runtime assumptions.

## Production assumptions
- An active `coordinator` agent exists.
- Active specialist agents exist for:
  - `strategy`
  - `finance`
  - `legal_compliance`
- The coordinator and specialists have agent profiles with explicit model preferences.
- Specialist agents expose the `analysis` capability expected by the current decomposition and assignment flow.
- `prompts.default.version` is configured explicitly.
- The configured `LlmProvider` binding resolves successfully.

## Runtime validation

Run:

```bash
php artisan company-loop:validate-production
```

The command validates:
- active coordinator availability
- required specialist-role coverage
- coordinator and specialist profile readiness
- explicit prompt versioning
- LLM provider resolution
- an end-to-end synthetic company-loop probe
- child task completion, succeeded executions, persisted artifacts, and communication-log creation
- rollback safety so probe records do not remain in the database after validation

The command emits structured JSON and exits non-zero when the company loop is not production-ready.

## Safe failure behavior

When production prerequisites are missing or the provider cannot complete the synthetic probe:
- validation fails with a structured JSON report
- the probe runs inside a database transaction and is rolled back
- no validation task, execution, artifact, or communication records remain persisted after the command finishes

This keeps validation safe in constrained environments while still proving the end-to-end company-loop path.
