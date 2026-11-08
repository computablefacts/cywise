# ITEM-020: Fix failing website tests

- Owner: Junie
- Base: main
- Branch: item/ITEM-020
- Status: done

## Description

Fix failing tests:
- `it serves the former changelog`
- `it renders localized website copy`

Both fail with 500 errors due to a missing `pentest` route name in Blade templates.

## Acceptance

- [✓] `it serves the former changelog` test passes.
- [✓] `it renders localized website copy` test passes.
- [✓] All `route('pentest')` calls are resolved correctly.
