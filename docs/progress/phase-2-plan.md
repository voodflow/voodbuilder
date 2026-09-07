# Phase 2 plan — Characterisation tests

## Goal

Protect current behaviour before structural moves. Make the suite trustworthy.

## Scope

1. Diagnose 16 PHPUnit failures + 1 Vitest failure (env vs stale expectation vs real bug).
2. Restore green suite without product rewrites where possible.
3. Add missing Feature coverage listed in master plan §20 (priority list).
4. Seed `tests/Fixtures/0.0.11/` from baseline samples.

## Files expected to change

- `tests/**` primarily
- Possibly minimal adapter/env wiring if tests require pinned Tailwind tooling
- `docs/progress/phase-2-characterisation.md` at end

## Risks

- “Fixing” tests by weakening assertions hides regressions
- Tailwind compile differs without host binary
- Touching renderer code accidentally changes HTML output

## Tests to run

- Filtered PHPUnit for previously failing classes
- Full PHPUnit before closing phase
- `npm run test:js`
