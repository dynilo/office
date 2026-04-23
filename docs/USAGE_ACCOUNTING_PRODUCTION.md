# Usage and Accounting Hardening

Usage accounting must stay reliable enough for future billing and operational reconciliation without redesigning the current runtime ledger.

## Production assumptions
- Internal usage is stored in `usage_accounting_records`.
- Provider token and estimated-cost data is stored in `provider_usage_records`.
- Core internal usage events are recorded with deterministic dedupe keys.
- Successful executions with provider responses should have a matching provider usage record when cost tracking is enabled.
- Provider usage token math must remain consistent: `input_tokens + output_tokens = total_tokens`.

## Runtime validation

Run:

```bash
php artisan usage-accounting:validate-runtime
```

The command validates:
- usage and provider usage tables exist
- `usage_accounting_records.dedupe_key` exists
- usage quantities are positive
- usage dedupe keys are unique
- succeeded executions with provider responses are not missing provider usage records when tracking is enabled
- provider token totals remain internally consistent

The command emits structured JSON and exits non-zero when accounting reliability assumptions are violated.

## Safe behavior

This validation is read-only. It does not mutate usage or cost records.

When cost tracking is intentionally disabled:
- provider usage completeness is not required
- internal usage validation still runs

When core runtime usage events are recorded:
- the application uses deterministic dedupe keys for `tasks.created`
- the application uses deterministic dedupe keys for `executions.created`
- the application uses deterministic dedupe keys for `executions.succeeded`
- the application uses deterministic dedupe keys for `executions.failed`
