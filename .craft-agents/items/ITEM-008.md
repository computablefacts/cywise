# ITEM-008: Auto-refresh items list

- Owner: Junie
- Base: 1.x
- Branch: item/ITEM-008
- Status: ready_to_merge

## Description

Improve the discovery logic in index.html to correctly populate the items list even when directory listing is disabled.
The previous plan to add a refresh button and auto-refresh was rejected by the user.

## Acceptance

- [x] The items list is correctly populated from existing files in the items/ directory.
- [x] No manual refresh button is present.
- [x] No automatic refresh timer is active.
- [x] The "Items" label above the list is removed.
