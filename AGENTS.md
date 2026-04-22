# AGENTS.md

This repository is being built from scratch as a Laravel 13 based AI Office OS.

## Product direction
The platform is a modular, API-first, real-time operating system for specialized AI agents.
It must support:
- persistent company memory
- task intake and routing
- agent registry and execution lifecycle
- document ingestion and retrieval
- structured outputs and auditability
- future multi-agent coordination

## Stack rules
Use these defaults unless a slice explicitly says otherwise:
- PHP 8.4+
- Laravel 13
- PostgreSQL
- Redis
- Pest for tests
- API-first architecture
- clean layered structure

Do not introduce alternative stacks without an explicit slice requirement.

## Architecture rules
Use a clean layered structure:
- app/Domain
- app/Application
- app/Infrastructure
- app/Support

Keep business logic out of controllers.
Prefer services/actions, repositories/contracts, DTOs/value objects, and focused tests.

## Delivery rules
For every slice:
- work only within the requested slice
- do not expand scope
- do not redesign unrelated parts
- generate complete working code, not pseudo-code
- add or update tests for the slice
- summarize changed files and assumptions at the end

## Behavior rules for Codex
When the repository is empty:
- bootstrap only the minimum foundation needed for the slice
- do not create speculative modules
- do not generate placeholder TODO-only files
- do not add infrastructure not requested by the slice

When a slice references docs or skills:
- read them first
- follow the acceptance criteria exactly
- prefer the simplest production-grade implementation that satisfies the slice

## Quality bar
Every slice should leave the repository in a working state.
No slice is complete unless:
- code is coherent
- tests exist
- acceptance criteria are satisfied
