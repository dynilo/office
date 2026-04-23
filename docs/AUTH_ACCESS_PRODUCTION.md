# Auth and Access Production Validation

The production auth surface is session-first for the admin shell and protected API access. Runtime validation should confirm that both authentication and role enforcement are present on the expected operational surfaces.

Production assumptions:
- the default guard is `web`
- the `web` guard uses Laravel session authentication
- the user provider is Eloquent-backed
- `/login` and `/logout` are present
- `/admin/*` routes require both `auth` and the allowed admin-role middleware
- `/api/admin/*` routes require both `auth` and the allowed admin-role middleware

Allowed admin roles:
- `super_admin`
- `admin`
- `operator`
- `observer`

Runtime validation:

```bash
php artisan auth:validate-runtime
```

This validation reports:
- auth guard and provider configuration
- the protected admin web and admin API surfaces
- role middleware alias registration
- allowed-role coverage on protected admin routes

Safe failure behavior:
- if required login/logout or protected-route middleware is missing, the command fails with a structured JSON report
- no auth state is mutated by the validation command
- the command validates route and configuration expectations only; it does not perform a browser login flow
