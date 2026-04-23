# Embedding Provider Production Validation

The embedding runtime supports a real provider-backed mode and an explicit degraded fallback mode.

## Production assumptions
- `EMBEDDING_PROVIDER=openai_compatible`
- `OPENAI_COMPATIBLE_BASE_URL` should be an HTTPS endpoint.
- `OPENAI_API_KEY` must be present for real embedding generation.
- `OPENAI_EMBEDDING_MODEL` must be configured explicitly.
- `MEMORY_EMBEDDING_DIMENSIONS` should match the real embedding model output dimensions.

## Runtime validation

Run:

```bash
php artisan embedding-provider:validate-runtime
```

The command validates:
- embedding provider selection
- model, base URL, and API key configuration
- live probe execution through the configured embedding generator
- normalized embedding model and vector output
- alignment between returned vector dimensions and `MEMORY_EMBEDDING_DIMENSIONS`

The command emits structured JSON and exits non-zero when the real embedding backend is not ready.

## Safe fallback

If a real embedding backend is unavailable in the current environment, the safe fallback is:

```dotenv
EMBEDDING_PROVIDER=null
```

In fallback mode:
- the null embedding generator is used explicitly
- similarity retrieval degrades to empty context blocks
- pgvector-backed storage/search readiness remains unavailable for real embeddings

Fallback mode is safe for local or constrained environments, but it is not production-ready for real memory retrieval.
