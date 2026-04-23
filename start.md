# star.md

You are working inside this repository in strict sequential slice mode.

## Primary rule
Read the slice list document and complete only the next unfinished slice in each run.

## Documents to read first
- AGENTS.md
- CODEX_SLICES_31_60.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

## Mandatory execution rules
1. Identify the next unfinished slice only.
2. Execute one and only one slice in this run.
3. Do not begin the following slice.
4. Do not merge multiple slices into one implementation.
5. Stay strictly inside the current slice scope.
6. Do not refactor unrelated areas.
7. Do not redesign unrelated architecture.
8. Generate complete working code, not pseudo-code.
9. Add or update tests required by the current slice.
10. If infrastructure is unavailable in the current environment, implement safe fallback behavior and make assumptions explicit.

## Required end-of-slice output
When the current slice is complete, you must:
1. run the relevant tests
2. summarize changed files
3. summarize assumptions
4. provide the exact commit command in this format:
   git add .
   git commit -m "Complete Slice XX ..."
   git push origin main
5. stop after the current slice

## Prohibited behavior
- Do not jump ahead
- Do not work on future slices
- Do not bundle multiple slices together
- Do not keep going after finishing the current slice
- Do not expand scope because something feels related

## Slice selection rule
- If Slice 31 is unfinished, do Slice 31
- otherwise do Slice 32
- otherwise do Slice 33
- continue in strict numeric order
- always stop after finishing exactly one slice

## If the current slice is ambiguous
Use the slice text in CODEX_SLICES_31_60.md as the source of truth.
Prefer minimal, production-oriented, test-covered implementation.