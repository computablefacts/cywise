# ITEM-003 Specs

## ITEM-003-S01: Supprimer les pages PSSI

Status: approved
Requirement: Supprimer les fichiers Blade correspondant aux pages de la PSSI.
Test: Vérifier que les fichiers suivants sont supprimés :
- `resources/themes/cywise/pages/pssi.blade.php`
- `resources/themes/cywise/pages/solutions/pssi.blade.php`
- `resources/themes/cywise/pages/en/solutions/pssi.blade.php`
- `resources/themes/cywise/pages/use-cases/create-pssi.blade.php`
- `resources/themes/cywise/pages/en/use-cases/create-pssi.blade.php`
Commit: -

## ITEM-003-S02: Retirer les liens PSSI des menus

Status: approved
Requirement: Supprimer les liens vers la PSSI dans le header, le footer et les sidebars.
Test: Vérifier que les chaînes "PSSI" ou "Politique de Sécurité des Systèmes d'Information" ne sont plus présentes dans :
- `resources/themes/cywise/components/marketing/elements/header.blade.php`
- `resources/themes/cywise/components/marketing/elements/footer.blade.php` (à vérifier)
- `resources/themes/cywise/components/elements/settings-sidebar-link.blade.php`
Commit: -

## ITEM-003-S03: Supprimer le template PSSI

Status: dropped
Requirement: Supprimer le fichier seeder de template pour la PSSI.
Test: Vérifier la suppression de `database/seeders/templates/pssi.json`.
Commit: -

## ITEM-003-S04: Nettoyer la documentation

Status: approved
Requirement: Retirer les mentions de la PSSI dans le README.
Test: Vérifier `README.md` et `README.en.md`.
Commit: -
