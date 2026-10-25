# ITEM-001 Specs

## ITEM-001-S01: Ajouter le lien Société dans le header

Status: completed
Requirement: Ajouter une entrée dans le tableau `$links` du fichier `header.blade.php` pour la page Société, placée entre "Cas d'usage" et "Blog".
Test: Tests unitaires dans `WebsiteRoutesTest.php` (assertSee 'Société'/'Company') et vérification manuelle.
Commit: cee0bbaf, 0233aa60
