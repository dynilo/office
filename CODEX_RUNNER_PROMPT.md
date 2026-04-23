Read first:
- AGENTS.md
- docs/CODEX_SLICES_31_60.md
- docs/ENGINEERING_RULES.md
- docs/SLICE_ROADMAP.md

You will execute the remaining slices strictly one at a time by reading docs/CODEX_SLICES_31_60.md.

Operational rules:
- Start from the next unfinished slice only.
- Complete exactly one slice per run.
- Stay inside the current slice scope.
- Do not jump ahead.
- Do not merge multiple slices into one implementation.
- At the end of the slice:
  1. run the relevant tests
  2. summarize changed files and assumptions
  3. stop and wait for the next run
- After code is complete, prepare the repository for:
  git add .
  git commit -m "Complete Slice XX ..."
  git push origin main

If a slice depends on missing infrastructure in the current environment, implement it with safe fallback behavior and make assumptions explicit in tests and summary.

Now read docs/CODEX_SLICES_31_60.md, identify the next unfinished slice, and execute only that slice.