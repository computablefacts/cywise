# ITEM-013 Specs

## ITEM-013-S01: Rename directory

Status: completed
Requirement: Rename the `.craft-agents` directory to `.ia-factory`.
Test: Directory `.ia-factory` exists and `.craft-agents` does not.
Commit: 8e5f29c

## ITEM-013-S02: Update references in AGENTS.md

Status: in_progress
Requirement: Update all references to `.craft-agents` in `AGENTS.md` to `.ia-factory`.
Test: `grep -r ".craft-agents" AGENTS.md` returns no results.
Commit: -

## ITEM-013-S03: Update references in workflow files

Status: in_progress
Requirement: Update all internal references within files inside the renamed directory.
Test: `grep -r ".craft-agents" .ia-factory` returns no results.
Commit: -
