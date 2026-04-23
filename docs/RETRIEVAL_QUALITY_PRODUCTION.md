# Retrieval Quality Production Validation

Retrieval quality must be explicit in production. The runtime already degrades safely to empty context blocks when no usable matches exist, but production readiness requires deterministic filtering and an explicit relevance threshold.

## Production assumptions
- `CONTEXT_RETRIEVAL_TOP_K` is set to a small bounded value.
- `CONTEXT_RETRIEVAL_MAX_DISTANCE` is configured explicitly.
- `MEMORY_VECTOR_DISTANCE` matches the intended similarity semantics.
- Retrieval should return deterministic ordering for equal-distance ties.
- Duplicate knowledge items should not appear in the selected context set.

## Runtime validation

Run:

```bash
php artisan retrieval:validate-quality
```

The command validates:
- retrieval `top_k` positivity and bounded size
- explicit threshold configuration
- threshold compatibility with the configured distance metric
- deterministic result ordering
- duplicate rejection
- threshold-based rejection
- safe empty-result behavior

The command emits structured JSON and exits non-zero when retrieval quality settings are incomplete or unsafe.

## Safe fallback

When retrieval has no acceptable matches, the safe runtime behavior is:
- return an empty context block set
- continue execution without injected retrieved context

This is safe fallback behavior. It is not a substitute for configuring a real threshold for production retrieval quality.
