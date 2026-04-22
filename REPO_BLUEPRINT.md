# ENGINEERING_RULES.md

## General rules
- Follow Laravel 13 best practices
- Use PHP 8.4+ syntax responsibly
- Keep the code production-oriented
- Prefer clarity over cleverness
- Do not add code that is not required by the current slice

## Scope discipline
- Implement only the requested slice
- Do not expand into future slices
- Do not refactor unrelated areas
- Do not add speculative abstractions unless the current slice clearly needs them

## Testing rules
- Every slice must include tests or test updates
- Tests should prove the acceptance criteria
- Prefer focused feature and unit tests
- Do not add decorative tests with no real coverage value

## API rules
- Prefer API-first patterns
- Keep controller logic thin
- Validate incoming payloads explicitly
- Return stable JSON structures

## Persistence rules
- Prefer PostgreSQL-first choices
- Use Redis for queue/cache where required
- Use migrations intentionally
- Add indexes and foreign keys with clear purpose

## Logging and safety
- Do not log secrets
- Normalize external-provider errors
- Keep operational behavior observable

## Done definition
A slice is not done unless:
- the requested code exists
- the tests pass
- the acceptance criteria are met
- changed files and assumptions are summarized
