# ITEM-018: Fix recipe page rendering

- Owner: Junie
- Base: main
- Branch: item/ITEM-018
- Status: review

## Description

The recipe page `resources/themes/cywise/pages/recipe/[id].blade.php` fails with `VoltDirectiveMissingException` because it lacks `@volt` directives while defining a Volt component.
To avoid using `@volt` directives in the Folio page, the Volt logic and template will be extracted into a dedicated Livewire component.

## Acceptance

- [ ] A new Volt component `resources/views/livewire/recipe-viewer.blade.php` is created.
- [ ] The Folio page `resources/themes/cywise/pages/recipe/[id].blade.php` is simplified to call this component.
- [ ] The recipe page loads without `VoltDirectiveMissingException`.
- [ ] Livewire actions (like download script) work as expected.
