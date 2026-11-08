# ITEM-018 Specs

## ITEM-018-S01: Extract Volt component

Status: approved
Requirement: Create `resources/views/livewire/recipe-viewer.blade.php` containing the component logic and template.
Test: File existence and syntax check.

## ITEM-018-S02: Update Folio page

Status: approved
Requirement: Simplify `resources/themes/cywise/pages/recipe/[id].blade.php` to call `<livewire:recipe-viewer :id="$id" />`.
Test: Manual verification of page loading without exception.

## ITEM-018-S03: Verify Livewire functionality

Status: approved
Requirement: Ensure the "Download remediation script" button works in the new component.
Test: Manual verification of the download action.
