# ITEM-008 Specs

## ITEM-008-S01: Add refresh button

Status: completed
Requirement: Add a manual refresh button in the sidebar near the Items header.
Test: Visual check of the button presence and functionality.
Commit: -

## ITEM-008-S02: Auto-refresh mechanism

Status: completed
Requirement: Implement a periodic refresh (every 60s) of the items list.
Test: Observe the list updating without manual intervention when files change.
Commit: -

## ITEM-008-S03: Improve list discovery

Status: completed
Requirement: Improve the discovery logic to handle environments where directory listing is disabled.
Test: Verify items are loaded even if `fetch('items/')` returns a 404/403 but files exist.
Commit: -
