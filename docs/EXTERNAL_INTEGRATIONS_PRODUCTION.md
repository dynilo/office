# External Integrations Production Readiness

The integration gateway must be explicit about which connectors are available, which are enabled, and whether the runtime is still operating in stub-only fallback mode.

## Production assumptions
- `INTEGRATIONS_DEFAULT` points to a configured connector.
- Each configured connector uses a supported driver.
- Disabled connectors must not be dispatchable.
- Enabled connectors expose at least one operation capability.
- Stub-only mode is safe for local or constrained environments.
- Stub-only mode is not production-ready unless explicitly allowed.

## Runtime validation

Run:

```bash
php artisan integrations:validate-runtime
```

The command validates:
- default connector selection
- connector configuration presence
- supported driver usage
- gateway descriptor resolution
- enabled connector availability
- enabled connector capability exposure
- whether the runtime is still in stub-only mode

The command emits structured JSON and exits non-zero when integration readiness assumptions are not met.

## Safe fallback behavior

When real external integrations are unavailable:
- the stub connector may remain configured for non-production use
- disabled connectors reject dispatch attempts
- the readiness command reports stub-only mode explicitly

If production intentionally needs stub-only fallback, set:

```dotenv
INTEGRATIONS_ALLOW_STUB_FALLBACK_IN_PRODUCTION=true
```

That keeps the behavior explicit instead of silently treating a stub transport as a real external integration.
