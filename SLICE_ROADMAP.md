# REPO_BLUEPRINT.md

## Top-level direction
This repository starts minimal and grows slice by slice.

## Expected early structure
- AGENTS.md
- README.md
- docs/
- skills/
- app/
- bootstrap/
- config/
- database/
- public/
- resources/
- routes/
- storage/
- tests/

## Application layering
As the codebase grows, prefer these top-level application folders:
- `app/Domain`
- `app/Application`
- `app/Infrastructure`
- `app/Support`

## Early conventions
- Controllers stay thin
- Business logic belongs in services/actions or domain/application classes
- Persistence concerns should be isolated behind contracts when useful
- Use DTOs/value objects where they improve clarity
- Add tests with each slice
