# pgvector Production Readiness

The memory layer is designed to degrade safely when PostgreSQL or pgvector is unavailable. In non-pgvector environments, embeddings metadata is still persisted and similarity search returns an empty result set.

Production assumptions:
- PostgreSQL is the production database.
- The `vector` extension should be installed before enabling vector-backed search.
- `knowledge_items.embedding` should exist as `vector(MEMORY_EMBEDDING_DIMENSIONS)`.
- The default index strategy is `hnsw` with `vector_cosine_ops`.

Configuration:
- `MEMORY_EMBEDDING_DIMENSIONS=1536`
- `MEMORY_VECTOR_DISTANCE=cosine`
- `PGVECTOR_EXTENSION_NAME=vector`
- `PGVECTOR_REQUIRE_IN_PRODUCTION=false`
- `PGVECTOR_INDEX_ENABLED=true`
- `PGVECTOR_INDEX_METHOD=hnsw`
- `PGVECTOR_INDEX_NAME=knowledge_items_embedding_hnsw_idx`

`PgvectorCapabilitiesService::readinessReport()` provides a safe diagnostic report without requiring the runtime to fail when pgvector is unavailable. Set `PGVECTOR_REQUIRE_IN_PRODUCTION=true` only when production must fail readiness if pgvector storage/search is unavailable.

For live runtime validation against a PostgreSQL connection that should have pgvector available, run:

```bash
php artisan pgvector:validate-runtime
```

This runtime validation probes:
- installed pgvector extension visibility
- `knowledge_items.embedding` vector column support
- configured vector dimensions alignment
- live similarity operator execution for the configured distance mode

Fallback-safe behavior when pgvector is unavailable:
- embedding metadata is still persisted
- vector storage is treated as unavailable instead of crashing migrations or writes
- similarity search degrades to an empty result set

The command fails safely with structured JSON output and redacted connection errors when PostgreSQL or pgvector runtime support is unavailable.
