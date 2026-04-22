# build-agent-runtime-slice.md

Use this skill when a slice is about the runtime core.

## Objectives
- keep code aligned with layered architecture
- keep business logic out of controllers
- create only what the slice requires
- keep acceptance criteria as the hard boundary

## Working style
1. Read AGENTS.md and the referenced docs.
2. Restate the slice goal internally.
3. Implement only the minimum production-grade code needed.
4. Add or update tests that prove the slice is complete.
5. Summarize changed files and assumptions.

## Guardrails
- no scope expansion
- no speculative module generation
- no placeholder-only files
- no unrelated refactors
