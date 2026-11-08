# ITEM-015: Correction erreur ComponentNotFound sur la recette de remédiation

- Owner: Junie
- Base: main
- Branch: item/ITEM-015
- Status: ready

## Description

Correction de l'erreur `Livewire\Exceptions\ComponentNotFoundException` qui survient lors de l'accès à la page de recette de remédiation (`/recipe/[id]`).
L'erreur semble liée à une mauvaise utilisation de `@volt` à l'intérieur d'un composant Volt déjà défini par Folio.

## Acceptance

- [ ] La page `/recipe/{id}` s'affiche sans erreur 500.
- [ ] Le bouton "Télécharger le script de remédiation" fonctionne correctement.
- [ ] L'affichage de la recette fonctionne correctement.
