# PostgreSQL Production Alignment

The AI Office OS runtime is PostgreSQL-first in production. Local development and automated tests may use SQLite for speed, but production boots are guarded by `PostgresqlProductionReadiness`.

Production defaults:
- `DB_CONNECTION=pgsql`
- `DB_SEARCH_PATH=public`
- `DB_SSLMODE=require`, `verify-ca`, or `verify-full`
- `DB_ENFORCE_POSTGRESQL=true`
- `DB_REQUIRE_SSL_IN_PRODUCTION=true`

The readiness guard checks configuration only. It does not require a live PostgreSQL server, so diagnostics remain safe in build and CI environments.

Set `DB_ENFORCE_POSTGRESQL=false` only for controlled emergency maintenance or non-production environments.
