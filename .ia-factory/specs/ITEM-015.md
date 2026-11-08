# ITEM-015 Specs

## ITEM-015-S01: Retirer les balises @volt redondantes

Status: approved
Requirement: Supprimer les directives `@volt('recipe')` et `@endvolt` dans `resources/themes/cywise/pages/recipe/[id].blade.php` car le fichier est déjà traité comme un composant Volt par Folio.
Test: Chargement de la page sans erreur.
Commit: -
