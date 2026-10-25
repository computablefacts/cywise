# ITEM-008: Auto-refresh items list

- Owner: Junie
- Base: 1.x
- Branch: item/ITEM-008
- Status: ready

## Description

Make the items list in index.html refresh automatically and provide a manual refresh button.
The current implementation relies on directory listing which might fail and uses a stale fallback.

## Acceptance

- [ ] A refresh button is available in the sidebar.
- [ ] The list refreshes automatically every 60 seconds.
- [ ] The list is correctly populated from existing files in the items/ directory.
